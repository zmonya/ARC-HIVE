<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Fetch user details with position and department
$stmt = $pdo->prepare("SELECT users.*, departments.name AS department_name 
                       FROM users 
                       LEFT JOIN departments ON users.department_id = departments.id 
                       WHERE users.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all selected departments for the user
$stmt = $pdo->prepare("SELECT departments.name FROM user_departments
                       JOIN departments ON user_departments.department_id = departments.id
                       WHERE user_departments.user_id = ?");
$stmt->execute([$userId]);
$userDepartments = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch recent files
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY upload_date DESC LIMIT 5");
$stmt->execute([$userId]);
$recentFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5");
$stmt->execute([$userId]);
$notificationLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch activity logs
$stmt = $pdo->prepare("SELECT * FROM activity_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 5");
$stmt->execute([$userId]);
$activityLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all files for the file selection popup
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->execute([$userId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define the getFileIcon function
function getFileIcon($fileName)
{
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'pdf':
            return 'fas fa-file-pdf';
        case 'doc':
        case 'docx':
            return 'fas fa-file-word';
        case 'xls':
        case 'xlsx':
            return 'fas fa-file-excel';
        case 'jpg':
        case 'png':
            return 'fas fa-file-image';
        default:
            return 'fas fa-file';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="client.css">
</head>

<body>
    <!-- File Selection Popup -->
    <div class="popup-file-selection" id="fileSelectionPopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('fileSelectionPopup')">x</button>
        <h3>Select a Document</h3>
        <div class="search-container">
            <input type="text" id="fileSearch" placeholder="Search files..." oninput="filterFiles()">
        </div>
        <div class="view-toggle">
            <button id="thumbnailViewButton" class="active" onclick="switchView('thumbnail')">
                <i class="fas fa-th-large"></i> Thumbnails
            </button>
            <button id="listViewButton" onclick="switchView('list')">
                <i class="fas fa-list"></i> List
            </button>
        </div>
        <div id="fileDisplay" class="thumbnail-view masonry-grid">
            <?php foreach ($files as $file): ?>
                <div class="file-item" data-file-id="<?= $file['id'] ?>" data-file-name="<?= htmlspecialchars($file['file_name']) ?>">
                    <div class="file-icon">
                        <i class="<?= getFileIcon($file['file_name']) ?>"></i>
                    </div>
                    <p><?= htmlspecialchars($file['file_name']) ?></p>
                    <button class="select-file-button">Select</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- File Details Popup for Uploading -->
    <div class="popup-questionnaire" id="fileDetailsPopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('fileDetailsPopup')">x</button>
        <h3>File Details</h3>
        <form id="fileDetailsForm" enctype="multipart/form-data">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>
            <label for="purpose">Purpose:</label>
            <select id="purpose" name="purpose" required>
                <option value="">Select Purpose</option>
                <option value="Meeting Announcement">Meeting Announcement</option>
                <option value="Title Approval">Title Approval</option>
                <option value="Request Letter">Request Letter</option>
            </select>
            <label for="hardCopyAvailable">Hard Copy Available?</label>
            <input type="checkbox" id="hardCopyAvailable" name="hardCopyAvailable">
            <button type="submit" class="submit-button">Submit</button>
        </form>
    </div>

    <!-- Send File Popup -->
    <div class="popup-questionnaire" id="sendFilePopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('sendFilePopup')">x</button>
        <h3>Send File</h3>
        <form id="sendFileForm">
            <label for="recipients">Recipients:</label>
            <div class="input-container">
                <div class="tags-container"></div>
                <input id="recipients" autocomplete="off" placeholder="Type to search for users or departments...">
                <div id="suggestions" class="suggestions"></div>
            </div>
            <button type="submit" class="submit-button">Send</button>
        </form>
    </div>

    <!-- Hardcopy Storage Suggestion Popup -->
    <div class="popup-questionnaire" id="hardcopyStoragePopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('hardcopyStoragePopup')">x</button>
        <h3>Hardcopy Storage Suggestion</h3>
        <div id="storageSuggestion"></div>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <button class="toggle-btn" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="sidebar-title">Document Archival</h2>
        <a href="index.php" class="active"><i class="fas fa-home"></i><span class="link-text"> Dashboard</span></a>
        <a href="my-folder.php"><i class="fas fa-folder"></i><span class="link-text"> My Folder</span></a>
        
        <div class="dropdown">
            <button class="dropbtn">Departments</button>
            <div class="dropdown-content">
                <?php if (!empty($userDepartments)) : ?>
                    <?php foreach ($userDepartments as $department): ?>
                        <a href="department_folder.php?dept=<?= urlencode($department) ?>">
                            <?= htmlspecialchars($department) ?>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No Departments Available</p>
                <?php endif; ?>
            </div>
        </div>

        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
    
    <!-- Top Navigation -->
    <div class="top-nav">
        <h2>Dashboard</h2>
        <input type="text" placeholder="Search documents...">
        <i class="fas fa-history activity-log-icon" onclick="toggleActivityLog()"></i>
        <button id="hardcopyStorageButton">Recommend Storage</button>
    </div>

    <!-- Activity Log -->
    <div class="activity-log" id="activityLog" style="display: none;">
        <h3>Activity Log</h3>
        <div class="log-entries">
            <?php if (!empty($activityLogs)) : ?>
                <?php foreach ($activityLogs as $log) : ?>
                    <div class="log-entry">
                        <i class="fas fa-history"></i>
                        <p><?= htmlspecialchars($log['action']) ?></p>
                        <span><?= date('h:i A', strtotime($log['timestamp'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="log-entry">
                    <p>No recent activity.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- User ID and Calendar Section -->
        <div class="user-id-calendar-container">
            <div class="user-id">
                <img src="<?php echo $user['profile_pic'] ?? 'user.jpg'; ?>" alt="User Picture" class="user-picture">
                <div class="user-info">
                    <p class="user-name"><?php echo $user['full_name']; ?></p>
                    <p class="user-position"><?php echo $user['position']; ?></p>
                    <p class="user-department"><?php echo !empty($userDepartments) ? implode(', ', $userDepartments) : 'No Department'; ?></p>
                </div>
            </div>
            <div class="digital-calendar-clock">
                <p id="currentDate"></p>
                <p id="currentTime"></p>
            </div>
        </div>

        <!-- Upload and Notification Log Container -->
        <div class="upload-activity-container">
            <!-- Send a Document Button -->
            <div class="upload-file" id="upload">
                <h3>Send a Document</h3>
                <button id="selectDocumentButton">Select Document</button>
            </div>

            <!-- Upload File Button -->
            <div class="upload-file" id="fileUpload">
                <h3>Upload File</h3>
                <button id="uploadFileButton">Upload File</button>
            </div>

            <!-- Notification Log -->
            <div class="notification-log">
                <h3>Notifications</h3>
                <div class="log-entries">
                    <?php if (!empty($notificationLogs)) : ?>
                        <?php foreach ($notificationLogs as $notification) : ?>
                            <div class="log-entry">
                                <i class="fas fa-bell"></i>
                                <p><?= htmlspecialchars($notification['message']) ?></p>
                                <span><?= date('h:i A', strtotime($notification['timestamp'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="log-entry">
                            <p>No new notifications.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Document Types Section -->
        <div class="category-header">Document Types</div>
        <div class="category">
            <div class="category-item">
                <i class="fas fa-file-pdf"></i>
                <p>PDF</p>
            </div>
            <div class="category-item">
                <i class="fas fa-file-word"></i>
                <p>DOCUMENT</p>
            </div>
            <div class="category-item">
                <i class="fas fa-file-excel"></i>
                <p>EXCEL</p>
            </div>
        </div>

        <!-- Recent Files Section -->
        <h3>Recent Files</h3>
        <div class="file-section">
            <?php foreach ($recentFiles as $file): ?>
                <div class="file">
                    <i class="<?= getFileIcon($file['file_name']) ?>"></i>
                    <p><?= htmlspecialchars($file['file_name']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Error Popup -->
    <div class="popup-error" id="fileErrorPopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('fileErrorPopup')">X</button>
        <h3>Select A File First</h3>
    </div>

    <script>
        $(document).ready(function() {
            let selectedRecipients = []; // Store selected recipients
            let selectedFile = null; // Store selected file

            // Suggestive search for recipients
            $('#recipients').on('input', function() {
                const query = $(this).val().trim();
                if (query.length > 0) {
                    $.ajax({
                        url: 'get_recipients.php',
                        method: 'GET',
                        data: {
                            q: query
                        },
                        dataType: 'json',
                        success: function(data) {
                            $('#suggestions').empty().toggle(data.length > 0);

                            data.forEach(item => {
                                const type = item.type === 'user' ? 'user' : 'department';
                                const displayName = type === 'user' ? `User: ${item.username}` : `Department: ${item.name}`;
                                $('#suggestions').append(`<div class="suggestion-item" data-id="${item.id}" data-type="${type}">${displayName}</div>`);
                            });
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error("Error fetching data:", textStatus, errorThrown);
                        }
                    });
                } else {
                    $('#suggestions').hide();
                }
            });

            // Handle selection of a suggestion
            $(document).on('click', '.suggestion-item', function() {
                const id = $(this).data('id');
                const name = $(this).text();
                const type = $(this).data('type');

                if (!selectedRecipients.some(recipient => recipient.id === id)) {
                    selectedRecipients.push({
                        id,
                        name,
                        type
                    });
                    updateInput();
                }

                $('#suggestions').hide();
            });

            // Handle removal of a selected recipient
            $(document).on('click', '.tag-close', function() {
                const id = $(this).parent().data('id');
                selectedRecipients = selectedRecipients.filter(recipient => recipient.id !== id);
                updateInput();
            });

            // Update the input field with selected recipients
            function updateInput() {
                $('#recipients').val('').css('min-width', '100px');
                $('.tag').remove();

                selectedRecipients.forEach(recipient => {
                    $('#recipients').before(`<span class="tag" data-id="${recipient.id}" data-type="${recipient.type}">${recipient.name}<span class="tag-close">&times;</span></span>`);
                });
            }

            $('#sendFileForm').on('submit', function(event) {
                event.preventDefault();

                const formData = new FormData();
                formData.append("file_id", selectedFile.id);

                // Append selected recipients
                selectedRecipients.forEach(recipient => {
                    formData.append("recipients[]", `${recipient.type}:${recipient.id}`);
                });

                // Send data to send_file_handler.php
                fetch('send_file_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = data.redirect; // Redirect on success
                        } else {
                            alert("Send failed: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("An error occurred while sending the file.");
                    });
            });

            // Toggle sidebar
            document.querySelector('.toggle-btn').addEventListener('click', function() {
                const sidebar = document.querySelector('.sidebar');
                const topNav = document.querySelector('.top-nav');

                sidebar.classList.toggle('minimized');
                topNav.classList.toggle('resized', sidebar.classList.contains('minimized'));
            });

            // Close all popups
            function closeAllPopups() {
                $('.popup-file-selection, #fileDetailsPopup, #sendFilePopup, #hardcopyStoragePopup, #activityLog').hide();
            }

            // Toggle activity log
            window.toggleActivityLog = function() {
                const activityLog = document.getElementById("activityLog");
                if ($(activityLog).is(':visible')) {
                    $(activityLog).hide();
                } else {
                    closeAllPopups();
                    $(activityLog).show();
                }
            };

            // Close specific popups
            window.closePopup = function(popupId) {
                $('#' + popupId).hide();
            };

            // Close activity log when clicking outside
            $(document).on('click', function(event) {
                const activityLog = document.getElementById("activityLog");
                const activityLogIcon = document.querySelector('.activity-log-icon');

                if (!$(event.target).closest(activityLog).length && !$(event.target).closest(activityLogIcon).length) {
                    $(activityLog).hide();
                }
            });

            // Update date and time
            function updateDateTime() {
                const now = new Date();
                const options = {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                };
                const currentDate = now.toLocaleDateString('en-US', options);
                const currentTime = now.toLocaleTimeString('en-US');

                $('#currentDate').text(currentDate);
                $('#currentTime').text(currentTime);
            }

            // Update the clock every second
            setInterval(updateDateTime, 1000);
            updateDateTime(); // Initial call

            // Open file selection popup for sending
            $('#selectDocumentButton').on('click', function() {
                closeAllPopups();
                $('#fileSelectionPopup').show();
            });

           // Handle file selection for sending
           $(document).on('click', '.select-file-button', function() {
                const fileItem = $(this).closest('.file-item');
                const fileId = fileItem.data('file-id');
                const fileName = fileItem.data('file-name');

                selectedFile = {
                    id: fileId,
                    name: fileName
                };
                closePopup('fileSelectionPopup');
                $('#sendFilePopup').show(); // Open send file popup
            });

            // Open file details popup for uploading
            $('#uploadFileButton').on('click', function() {
                closeAllPopups();
                const fileInput = $('<input type="file" id="fileInput" style="display: none;">');
                $('body').append(fileInput);
                fileInput.trigger('click');

                fileInput.on('change', function() {
                    const file = this.files[0];
                    if (file) {
                        selectedFile = {
                            name: file.name,
                            file: file
                        };
                        $('#fileDetailsPopup').show(); // Open file details popup
                    }
                });
            });

            $('#fileDetailsForm').on('submit', function(event) {
                event.preventDefault();

                const formData = new FormData();
                formData.append("file", selectedFile.file); // Append the file
                formData.append("subject", $('#subject').val()); // Append subject
                formData.append("purpose", $('#purpose').val()); // Append purpose
                formData.append("hardCopyAvailable", $('#hardCopyAvailable').is(':checked') ? 1 : 0); // Append hardCopyAvailable

                // Send data to upload_handler.php
                fetch('upload_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = data.redirect; // Redirect on success
                        } else {
                            alert("Upload failed: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("An error occurred while uploading the file.");
                    });
            });

            // Handle hardcopy storage button click
            $('#hardcopyStorageButton').on('click', function() {
                closeAllPopups();
                $('#hardcopyStoragePopup').show();

                // Fetch storage suggestion
                fetch('get_storage_suggestion.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            $('#storageSuggestion').text(data.suggestion);
                        } else {
                            $('#storageSuggestion').text("No storage suggestion available.");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        $('#storageSuggestion').text("An error occurred while fetching storage suggestion.");
                    });
            });

            // Switch between thumbnail and list views
            window.switchView = function(view) {
                const fileDisplay = document.getElementById('fileDisplay');
                const thumbnailButton = document.getElementById('thumbnailViewButton');
                const listButton = document.getElementById('listViewButton');

                if (view === 'thumbnail') {
                    fileDisplay.classList.remove('list-view');
                    fileDisplay.classList.add('thumbnail-view', 'masonry-grid');
                    thumbnailButton.classList.add('active');
                    listButton.classList.remove('active');
                } else {
                    fileDisplay.classList.remove('thumbnail-view', 'masonry-grid');
                    fileDisplay.classList.add('list-view');
                    listButton.classList.add('active');
                    thumbnailButton.classList.remove('active');
                }
            };

            // Filter files based on search input
            $('#fileSearch').on('input', filterFiles);

            function filterFiles() {
                const searchQuery = document.getElementById('fileSearch').value.toLowerCase();
                const fileItems = document.querySelectorAll('.file-item');

                fileItems.forEach(item => {
                    const fileName = item.dataset.fileName.toLowerCase();
                    item.style.display = fileName.includes(searchQuery) ? 'block' : 'none';
                });
            }
        });

        // Optional: Toggle dropdown on click
        document.querySelector('.dropbtn').addEventListener('click', function() {
            const dropdownContent = document.querySelector('.dropdown-content');
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
        });

        // Close dropdown if clicked outside
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.dropbtn')) {
                const dropdowns = document.getElementsByClassName("dropdown-content");
                for (let i = 0; i < dropdowns.length; i++) {
                    dropdowns[i].style.display = "none";
                }
            }
        });
    </script>

    <style>
        .sidebar .nav-link {
            color: white;
        }

        .sidebar .nav-link:hover {
            background-color: #343a40; /* Darker background on hover */
        }

        .sidebar .dropdown-item:hover {
            background-color: #495057; /* Darker background for dropdown items */
        }

        /* Dropdown button styling */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            background-color: transparent;
            color: white;
            padding: 10px;
            font-size: 16px;
            border: none;
            cursor: pointer;
            text-align: left;
            width: 100%;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }

        .dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        /* Optional: Change style on hover */
        .dropdown:hover .dropbtn {
            background-color: #ddd;
            color: black;
        }
    </style>
</body>

</html>
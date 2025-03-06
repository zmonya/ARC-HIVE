<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db_connection.php';

$user_id = $_SESSION['user_id'];

// Fetch user information
$sql = "SELECT full_name, position, department, profile_pic, role FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user['role'] !== 'client') {
    header("Location: unauthorized.php"); // Redirect to an unauthorized page
    exit();
}


// Fetch all departments for selection
$sqlDepartments = "SELECT id, name FROM departments";
$resultDepartments = $conn->query($sqlDepartments);
$departments = $resultDepartments->fetch_all(MYSQLI_ASSOC);

// Fetch activity logs for the user
$sqlActivity = "SELECT action, timestamp FROM activity_log WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10";
$stmtActivity = $conn->prepare($sqlActivity);
$stmtActivity->bind_param("i", $user_id);
$stmtActivity->execute();
$resultActivity = $stmtActivity->get_result();
$activityLogs = $resultActivity->fetch_all(MYSQLI_ASSOC);
$stmtActivity->close();

// Fetch notifications for the user
$sqlNotifications = "SELECT message, timestamp, icon, type FROM notifications WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10";
$stmtNotifications = $conn->prepare($sqlNotifications);
$stmtNotifications->bind_param("i", $user_id);
$stmtNotifications->execute();
$resultNotifications = $stmtNotifications->get_result();
$notificationLogs = $resultNotifications->fetch_all(MYSQLI_ASSOC);
$stmtNotifications->close();

// Close the database connection
$conn->close();
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <link rel="stylesheet" href="styles.css">
    <style>
        .notification-log {
            background-color: #f8f9fa;
            /* Light background */
            border-radius: 8px;
            padding: 20px;
        }

        .log-entries {
            margin-top: 10px;
        }

        .log-entry {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            margin: 5px 0;
        }

        .success {
            background-color: #d4edda;
            /* Light green */
            color: #155724;
            /* Dark green */
        }

        .error {
            background-color: #f8d7da;
            /* Light red */
            color: #721c24;
            /* Dark red */
        }

        .info {
            background-color: #d1ecf1;
            /* Light blue */
            color: #0c5460;
            /* Dark blue */
        }

        .log-entry i {
            margin-right: 10px;
            /* Space between icon and text */
        }

        .fas.fa-envelope-circle-check {
            color: #32cd32;
            /* Green for received icon */
        }

        .fas.fa-check-circle {
            color: #45b6fe;
            /* Green for success icon */
        }

        .fas.fa-exclamation-circle {
            color: #dc3545;
            /* Red for error icon */
        }

        #fileDetailsForm {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            max-width: 450px;
            margin: 0 auto;
        }

        .input-container {
            position: relative;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 5px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            width: 100%;
            background-color: #f8fafc;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            min-height: 45px;
        }

        .tag {
            display: flex;
            align-items: center;
            padding: 6px 12px;
            background-color: #50c878;
            color: white;
            border-radius: 20px;
            font-size: 14px;
            box-shadow: 0 1px 5px rgba(0, 0, 0, 0.2);
        }

        .tag-close {
            margin-left: 8px;
            cursor: pointer;
            color: white;
            font-weight: bold;
        }

        #recipients {
            flex-grow: 1;
            border: none;
            outline: none;
            font-size: 16px;
            background: #f8fafc;
            height: 45px;
            min-width: 150px;
        }

        .suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            max-height: 150px;
            overflow-y: auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            z-index: 10;
            display: none;
        }

        .suggestion-item {
            padding: 12px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .suggestion-item:hover {
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
   <!-- Sidebar -->
<div class="sidebar">
    <h2>Document Archival</h2>
    <a href="users.php"><i class="fas fa-users"></i> Users</a>
    <a href="departments.php"><i class="fas fa-building"></i> Departments</a>
    <a href="files.php"><i class="fas fa-file"></i> Files</a>
    <a href="recently_deleted.php"><i class="fas fa-trash"></i> Recently Deleted</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
</div>


    <!-- Top Navigation -->
    <div class="top-nav">
        <h2>Admin Dashboard</h2>
        <input type="text" placeholder="Search documents...">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <i class="fas fa-history activity-log-icon" onclick="toggleActivityLog()"></i>
    </div>

    <!-- Activity Log Dropdown -->
    <div class="activity-log" id="activityLog">
        <h3>Activity Log</h3>
        <div class="log-entries">
            <?php foreach ($activityLogs as $log): ?>
                <div class="log-entry">
                    <i class="fas fa-file-upload"></i> <!-- Change icon based on action -->
                    <p><?= htmlspecialchars($log['action']) ?></p>
                    <span><?= date('h:i A', strtotime($log['timestamp'])) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- User ID and Calendar Section -->
        <div class="user-id-calendar-container">
            <!-- User ID GUI -->
            <div class="user-id">
                <img src="<?php echo $user['profile_pic'] ?? 'user.jpg'; ?>" alt="User Picture" class="user-picture">
                <div class="user-info">
                    <p class="user-name"><?php echo $user['full_name']; ?></p>
                    <p class="user-position"><?php echo $user['position']; ?></p>
                    <p class="user-department"><?php echo $user['department']; ?></p>
                </div>
            </div>

            <!-- Digital Calendar and Clock -->
            <div class="digital-calendar-clock">
                <p id="currentDate"></p>
                <p id="currentTime"></p>
            </div>
        </div>

        <!-- Upload and Notification Log Container -->
        <div class="upload-activity-container">

            <!-- Upload Section -->
            <div class="upload-file" id="upload">
                <h3>Upload a Document</h3>
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="file" name="document" id="document" required>
                    <button type="button" id="uploadButton">Upload</button>
                </form>
            </div>



            
            <!-- Notification Log Section -->
            <div class="notification-log">
                <h3>Notification</h3>
                <div class="log-entries">
                    <?php foreach ($notificationLogs as $notification): ?>
                        <div class="log-entry <?= htmlspecialchars($notification['type']) ?>">
                            <i class="<?= htmlspecialchars($notification['icon']) ?>"></i>
                            <p><?= htmlspecialchars($notification['message']) ?></p>
                            <span><?= date('h:i A', strtotime($notification['timestamp'])) ?></span>
                        </div>
                    <?php endforeach; ?>
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
            <div class="file">
                <i class="fas fa-file-pdf"></i>
                <p>Report.pdf</p>
            </div>
            <div class="file">
                <i class="fas fa-file-word"></i>
                <p>Proposal.docx</p>
            </div>
            <div class="file">
                <i class="fas fa-file-excel"></i>
                <p>Data.xlsx</p>
            </div>
        </div>
    </div>

    <!-- Error Popup -->
    <div class="popup-error" id="fileErrorPopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('fileErrorPopup')">X</button>
        <h3>Select A File First</h3>
    </div>

    <!-- File Details Popup -->
    <div class="popup-questionnaire" id="fileDetailsPopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('fileDetailsPopup')">x</button>
        <h3>File Details</h3>
        <form id="fileDetailsForm">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>

            <label for="purpose">Purpose:</label>
            <select id="purpose" name="purpose" required>
                <option value="">Select Purpose</option>
                <option value="Meeting Announcement">Meeting Announcement</option>
                <option value="Title Approval">Title Approval</option>
                <option value="Request Letter">Request Letter</option>
            </select>

            <label for="recipients">Recipients:</label>
            <div class="input-container">
                <div class="tags-container">
                    <!-- Selected recipients will appear here -->
                </div>
                <input id="recipients" autocomplete="off" placeholder="Type to search for departments...">
                <div id="suggestions" class="suggestions"></div>
            </div>


            <button type="submit" class="submit-button">Submit</button>
        </form>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // ACTIVITY-LOG
        function toggleActivityLog() {
            const activityLog = document.getElementById("activityLog");
            if (activityLog.style.display === "none" || activityLog.style.display === "") {
                activityLog.style.display = "block";
            } else {
                activityLog.style.display = "none";
            }
        }

        $(document).ready(function() {
            let selectedRecipients = [];

            // Function to update the clock and date
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

            // Handle upload button click
            $('#uploadButton').on('click', function() {
                const fileInput = $('#document')[0];
                if (fileInput.files.length === 0) {
                    $('#fileErrorPopup').show();
                } else {
                    $('#fileDetailsPopup').show();
                }
            });

            // Close popups
            function closePopup(popupId) {
                document.getElementById(popupId).style.display = "none";
            }

            // Handle file details form submission
            $('#fileDetailsForm').on('submit', function(event) {
                event.preventDefault();

                const formData = new FormData();
                formData.append("document", $('#document')[0].files[0]);
                formData.append("subject", $('#subject').val());
                formData.append("purpose", $('#purpose').val());
                const recipients = selectedRecipients.map(recipient => recipient.id); // Get selected recipient IDs
                recipients.forEach(recipient => {
                    formData.append("recipients[]", recipient);
                });

                fetch('upload_handler.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = data.redirect;
                        } else {
                            alert("Upload failed: " + data.message);
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        alert("An error occurred while uploading the file.");
                    });
            });

            // Script for managing recipients
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
                            $('#suggestions').empty().toggle(data.departments.length > 0 || data.users.length > 0);

                            // Add department suggestions
                            data.departments.forEach(department => {
                                $('#suggestions').append(`<div class="suggestion-item" data-id="${department.id}" data-type="department">${department.name}</div>`);
                            });

                            // Add user suggestions
                            data.users.forEach(user => {
                                $('#suggestions').append(`<div class="suggestion-item" data-id="${user.id}" data-type="user">${user.username}</div>`);
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

            $(document).on('click', '.suggestion-item', function() {
                const id = $(this).data('id');
                const name = $(this).text();
                const type = $(this).data('type'); // Get the type (department or user)

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

            $(document).on('click', '.tag-close', function() {
                const id = $(this).parent().data('id');
                selectedRecipients = selectedRecipients.filter(recipient => recipient.id !== id);
                updateInput();
            });

            $(document).on('click', function(event) {
                if (!$(event.target).closest('#recipients, #suggestions').length) {
                    $('#suggestions').hide();
                }
            });

            function updateInput() {
                $('#recipients').val('').css('min-width', '100px');
                $('.tag').remove();

                selectedRecipients.forEach(recipient => {
                    $('#recipients').before(`<span class="tag" data-id="${recipient.id}" data-type="${recipient.type}">${recipient.name}<span class="tag-close">&times;</span></span>`);
                });
            }
        });

        function closePopup(popupId) {
            document.getElementById(popupId).style.display = "none";
        }
    </script>
</body>

</html>
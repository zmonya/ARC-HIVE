<?php
session_start(); // Start session

// Simulate a logged-in user for testing purposes
$_SESSION['user_id'] = 1; // Replace with a valid user ID from your database

require 'db_connection.php'; // Ensure the database connection is available

// Fetch all files for the file selection popup
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->execute([$_SESSION['user_id']]);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debugging: Log the start of the upload process
    error_log("File upload started for user: " . $_SESSION['user_id']);

    // Check if a file was uploaded
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        error_log("No file uploaded or file upload error.");
        echo json_encode(['success' => false, 'message' => 'No file uploaded or file upload error.']);
        exit();
    }

    // Validate session user ID
    if (!isset($_SESSION['user_id'])) {
        error_log("User not logged in.");
        echo json_encode(['success' => false, 'message' => 'User not logged in.']);
        exit();
    }

    $userId = $_SESSION['user_id'];
    $subject = trim(filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_STRING));
    $purpose = trim(filter_var($_POST['purpose'] ?? '', FILTER_SANITIZE_STRING));
    $recipients = $_POST['recipients'] ?? [];

    // Validate recipients
    if (!is_array($recipients)) {
        error_log("Invalid recipients format.");
        echo json_encode(['success' => false, 'message' => 'Invalid recipients format.']);
        exit();
    }

    // Ensure upload directory exists
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            error_log("Failed to create upload directory: " . $uploadDir);
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
            exit();
        }
        error_log("Created upload directory: " . $uploadDir);
    }

    // Sanitize file data
    $fileType = $_FILES['document']['type'];
    $fileSize = $_FILES['document']['size'];
    $fileName = basename($_FILES['document']['name']);
    $safeFileName = preg_replace('/[^a-zA-Z0-9\.\-_]/', '', $fileName);
    $filePath = $uploadDir . bin2hex(random_bytes(8)) . '_' . $safeFileName;

    // Validate file type and size
    $allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', // For .xls files
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' // For .xlsx files
    ];
    $maxSize = 10 * 1024 * 1024; // 10MB

    if (!in_array($fileType, $allowedTypes)) {
        error_log("Invalid file type: " . $fileType);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed types: PDF, JPEG, PNG, DOC, DOCX, XLS, XLSX.']);
        exit();
    }

    if ($fileSize > $maxSize) {
        error_log("File size exceeds limit: " . $fileSize);
        echo json_encode(['success' => false, 'message' => 'File size exceeds the limit of 10MB.']);
        exit();
    }

    try {
        // Move uploaded file
        if (is_uploaded_file($_FILES['document']['tmp_name']) && move_uploaded_file($_FILES['document']['tmp_name'], $filePath)) {
            error_log("File uploaded successfully: " . $filePath);

            // Insert file details into database
            $stmt = $pdo->prepare("INSERT INTO files (file_name, file_path, user_id, file_size, file_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$fileName, $filePath, $userId, $fileSize, $fileType]);

            $fileId = $pdo->lastInsertId();

            // Process recipients (users and departments)
            foreach ($recipients as $recipient) {
                $recipientData = explode(':', $recipient); // Format: "user:1" or "department:2"
                if (count($recipientData) !== 2) {
                    continue; // Skip invalid recipients
                }

                $type = $recipientData[0]; // "user" or "department"
                $id = $recipientData[1]; // ID of the user or department

                if ($type === 'user') {
                    // Validate user exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
                    $stmt->execute([$id]);
                    if (!$stmt->fetch()) {
                        continue; // Skip invalid users
                    }

                    // Insert into file_recipients table
                    $stmt = $pdo->prepare("INSERT INTO file_recipients (file_id, recipient_id) VALUES (?, ?)");
                    $stmt->execute([$fileId, $id]);

                    // Send notification to user
                    sendNotification($id, "You have received a new file: $fileName");
                } elseif ($type === 'department') {
                    // Validate department exists
                    $stmt = $pdo->prepare("SELECT id FROM departments WHERE id = ?");
                    $stmt->execute([$id]);
                    if (!$stmt->fetch()) {
                        continue; // Skip invalid departments
                    }

                    // Insert into file_departments table
                    $stmt = $pdo->prepare("INSERT INTO file_departments (file_id, department_id) VALUES (?, ?)");
                    $stmt->execute([$fileId, $id]);

                    // Send notification to all users in the department
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE department_id = ?");
                    $stmt->execute([$id]);
                    $departmentUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($departmentUsers as $userId) {
                        sendNotification($userId, "Your department has received a new file: $fileName");
                    }
                }
            }

            // Log activity
            logActivity($userId, "Uploaded file: $fileName");

            // Send notification to the uploader
            sendNotification($userId, "File uploaded successfully: $fileName");

            // Return success response with redirect URL
            echo json_encode(['success' => true, 'message' => 'File uploaded successfully!', 'file_id' => $fileId]);
        } else {
            error_log("Failed to move uploaded file.");
            echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
        }
    } catch (PDOException $e) {
        // Handle database errors
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A database error occurred. Please try again.']);
    }
} else {
    // Display the upload form
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>File Upload Test</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
        <style>
            /* Add your CSS styles here */
            .popup-file-selection,
            .popup-questionnaire {
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: white;
                border-radius: 10px;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
                padding: 20px;
                z-index: 1000;
                display: none;
            }

            .exit-button {
                position: absolute;
                top: 10px;
                right: 10px;
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
            }

            .masonry-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 15px;
            }

            .file-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 8px;
            }

            .file-icon {
                font-size: 48px;
                color: #2c3e50;
                margin-bottom: 10px;
            }

            .select-file-button {
                background-color: #50c878;
                color: white;
                border: none;
                border-radius: 5px;
                padding: 5px 10px;
                cursor: pointer;
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
        <h1>File Upload Test</h1>
        <button id="selectDocumentButton">Select Document</button>

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

        <!-- File Details Popup -->
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
                <label for="recipients">Recipients:</label>
                <div class="input-container">
                    <div class="tags-container"></div>
                    <input id="recipients" autocomplete="off" placeholder="Type to search for users or departments...">
                    <div id="suggestions" class="suggestions"></div>
                </div>
                <button type="submit" class="submit-button">Submit</button>
            </form>
        </div>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            $(document).ready(function() {
                let selectedRecipients = []; // Store selected recipients
                let selectedFile = null; // Store selected file

                // Open file selection popup
                $('#selectDocumentButton').on('click', function() {
                    $('#fileSelectionPopup').show();
                });

                // Handle file selection from the list of uploaded files
                $(document).on('click', '.select-file-button', function() {
                    const fileItem = $(this).closest('.file-item');
                    const fileId = fileItem.data('file-id');
                    const fileName = fileItem.data('file-name');

                    selectedFile = {
                        id: fileId,
                        name: fileName
                    };
                    $('#fileNameDisplay').text(fileName); // Display selected file name
                    $('#fileSelectionPopup').hide();
                    $('#fileDetailsPopup').show(); // Open details popup
                });

                // Handle file details form submission for sending already uploaded files
                $('#fileDetailsForm').on('submit', function(event) {
                    event.preventDefault();

                    const formData = new FormData();
                    formData.append("file_id", selectedFile.id); // Send the file_id of the selected file
                    formData.append("subject", $('#subject').val());
                    formData.append("purpose", $('#purpose').val());

                    // Append selected recipients
                    selectedRecipients.forEach(recipient => {
                        formData.append("recipients[]", `${recipient.type}:${recipient.id}`);
                    });

                    // Debugging: Log FormData entries
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ', ' + pair[1]);
                    }

                    // Send data to upload_handler.php
                    fetch('upload_handler.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert("File sent successfully!");
                                window.location.href = data.redirect; // Redirect on success
                            } else {
                                alert("Failed to send file: " + data.message);
                            }
                        })
                        .catch(error => {
                            console.error("Error:", error);
                            alert("An error occurred while sending the file.");
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

                $(document).on('click', '.tag-close', function() {
                    const id = $(this).parent().data('id');
                    selectedRecipients = selectedRecipients.filter(recipient => recipient.id !== id);
                    updateInput();
                });

                function updateInput() {
                    $('#recipients').val('').css('min-width', '100px');
                    $('.tag').remove();

                    selectedRecipients.forEach(recipient => {
                        $('#recipients').before(`<span class="tag" data-id="${recipient.id}" data-type="${recipient.type}">${recipient.name}<span class="tag-close">&times;</span></span>`);
                    });
                }

                // Close popups
                window.closePopup = function(popupId) {
                    $('#' + popupId).hide();
                };
            });
        </script>
    </body>

    </html>
<?php
}
?>
<?php
session_start(); // Start session

require 'db_connection.php';
require 'log_activity.php';
require 'notification.php';

global $pdo; // Ensure PDO connection is available

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("Process started for user: " . $_SESSION['user_id']);

    // Debug: Log POST and FILES data
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));

    // Check if a file is uploaded
    if (isset($_FILES['file'])) {
        // Handle file upload logic
        if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            error_log("No file uploaded or file upload error.");
            echo json_encode(['success' => false, 'message' => 'No file uploaded or file upload error.']);
            exit();
        }

        $userId = $_SESSION['user_id'];
        $subject = trim(filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
        $purpose = trim(filter_var($_POST['purpose'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
        $hardCopyAvailable = isset($_POST['hardCopyAvailable']) ? 1 : 0;

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
        $fileType = $_FILES['file']['type'];
        $fileSize = $_FILES['file']['size']; // Get file size in bytes
        $fileName = basename($_FILES['file']['name']);
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
            if (is_uploaded_file($_FILES['file']['tmp_name']) && move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
                error_log("File uploaded successfully: " . $filePath);

                // Insert file details into database
                $stmt = $pdo->prepare("INSERT INTO files (file_name, file_path, user_id, file_size, file_type, subject, purpose, hard_copy_available) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fileName, $filePath, $userId, $fileSize, $fileType, $subject, $purpose, $hardCopyAvailable]);
                $fileId = $pdo->lastInsertId();

                // Log activity
                logActivity($userId, "Uploaded file: $fileName");

                // Send notification to the uploader
                sendNotification($userId, "File uploaded successfully: $fileName");

                // Return success response with redirect URL
                echo json_encode(['success' => true, 'message' => 'File uploaded successfully!', 'redirect' => 'my-folder.php']);
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
        error_log("No file uploaded.");
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    }
} else {
    error_log("Invalid request method.");
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

<?php
session_start();
require 'db_connection.php'; // Include database connection

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the uploaded file and form data
$file = $_FILES['document'];
$subject = $_POST['subject'] ?? '';
$purpose = $_POST['purpose'] ?? '';
$recipients = $_POST['recipients'] ?? []; // This will be an array of recipient IDs

// Create a directory for the user if it doesn't exist
$uploadDir = 'uploads/' . $user_id . '/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Define file path
$filePath = $uploadDir . basename($file['name']);

// Move the uploaded file to the user's directory
if (move_uploaded_file($file['tmp_name'], $filePath)) {
    // Prepare to save file metadata in the database
    $stmt = $conn->prepare("INSERT INTO files (file_name, file_path, purpose, subject, recipients, user_id, file_size, file_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $fileSize = $file['size'];
    $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
    $recipientsList = implode(',', $recipients); // Store recipients as a comma-separated string

    $stmt->bind_param("sssssisi", $file['name'], $filePath, $purpose, $subject, $recipientsList, $user_id, $fileSize, $fileType);

    if ($stmt->execute()) {
        // Log the upload action
        $action = "Uploaded \"" . htmlspecialchars($file['name']) . "\"";
        $logStmt = $conn->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
        $logStmt->bind_param("is", $user_id, $action);
        $logStmt->execute();
        $logStmt->close();

        // Notify the uploader of successful upload
        $notifyMessage = "Your file \"" . htmlspecialchars($file['name']) . "\" was uploaded successfully.";
        $notifyIcon = "fas fa-check-circle"; // Success icon
        $notifyType = "success"; // Class for success notification
        $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, message, icon, type) VALUES (?, ?, ?, ?)");
        $notifyStmt->bind_param("isss", $user_id, $notifyMessage, $notifyIcon, $notifyType);
        $notifyStmt->execute();
        $notifyStmt->close();

        // Notify recipients
        $recipientNames = [];
        $senderNameStmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $senderNameStmt->bind_param("i", $user_id);
        $senderNameStmt->execute();
        $senderNameStmt->bind_result($senderName);
        $senderNameStmt->fetch();
        $senderNameStmt->close();

        foreach ($recipients as $recipientId) {
            $recipientNotifyMessage = "<strong>" . htmlspecialchars($senderName) . "</strong> sent \"" . htmlspecialchars($file['name']) . "\" to you.";
            $recipientNotifyIcon = "fas fa-envelope-circle-check"; // Info icon
            $recipientNotifyType = "info"; // Class for info notification
            $recipientNotifyStmt = $conn->prepare("INSERT INTO notifications (user_id, message, icon, type) VALUES (?, ?, ?, ?)");
            $recipientNotifyStmt->bind_param("isss", $recipientId, $recipientNotifyMessage, $recipientNotifyIcon, $recipientNotifyType);
            $recipientNotifyStmt->execute();
            $recipientNotifyStmt->close();
        }

        // Redirect to my-folder.php
        echo json_encode(['success' => true, 'redirect' => 'my-folder.php']);
    } else {
        // Notify uploader of failure to save file metadata
        $notifyMessage = "Failed to save file metadata for \"" . htmlspecialchars($file['name']) . "\".";
        $notifyIcon = "fas fa-exclamation-circle"; // Error icon
        $notifyType = "error"; // Class for error notification
        $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, message, icon, type) VALUES (?, ?, ?, ?)");
        $notifyStmt->bind_param("isss", $user_id, $notifyMessage, $notifyIcon, $notifyType);
        $notifyStmt->execute();
        $notifyStmt->close();

        echo json_encode(['success' => false, 'message' => 'Failed to save file metadata.']);
    }
    $stmt->close();
} else {
    // Notify uploader of failure to upload
    $notifyMessage = "Failed to upload file \"" . htmlspecialchars($file['name']) . "\".";
    $notifyIcon = "fas fa-exclamation-circle"; // Error icon
    $notifyType = "error"; // Class for error notification
    $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, message, icon, type) VALUES (?, ?, ?, ?)");
    $notifyStmt->bind_param("isss", $user_id, $notifyMessage, $notifyIcon, $notifyType);
    $notifyStmt->execute();
    $notifyStmt->close();

    echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
}

$conn->close();
<?php
session_start(); // Start session

require 'db_connection.php';
require 'log_activity.php';
require 'notification.php';

global $pdo; // Ensure PDO connection is available

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate a unique request ID to prevent duplicate processing
    $requestId = md5(uniqid(mt_rand(), true));

    // Check if this request has already been processed
    if (isset($_SESSION['last_request_id']) && $_SESSION['last_request_id'] === $requestId) {
        echo json_encode(['success' => false, 'message' => 'Duplicate request detected.']);
        exit();
    }

    // Store the request ID in the session
    $_SESSION['last_request_id'] = $requestId;

    $fileId = $_POST['file_id'];
    $userId = $_SESSION['user_id'];
    $recipients = $_POST['recipients'] ?? [];

    // Fetch file details
    $stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$file) {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
        exit();
    }

    $fileName = $file['file_name'];

    // Process recipients
    foreach ($recipients as $recipient) {
        $recipientData = explode(':', $recipient); // Format: "user:1" or "department:2"
        if (count($recipientData) !== 2) {
            continue; // Skip invalid recipients
        }

        $type = $recipientData[0]; // "user" or "department"
        $id = $recipientData[1]; // ID of the user or department

        if ($type === 'user') {
            // Insert into file_recipients table
            $stmt = $pdo->prepare("INSERT INTO file_recipients (file_id, recipient_id) VALUES (?, ?)");
            $stmt->execute([$fileId, $id]);

            // Send notification to user
            sendNotification($id, "You have received a new file: $fileName");
        } elseif ($type === 'department') {
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
    logActivity($userId, "Sent file: $fileName");

    // Return success response
    echo json_encode(['success' => true, 'message' => 'File sent successfully!', 'redirect' => 'my-folder.php']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

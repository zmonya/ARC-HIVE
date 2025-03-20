<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'suggestion' => 'User not logged in.']);
    exit();
}

// Get form data
$subject = trim($_POST['subject'] ?? '');
$purpose = trim($_POST['purpose'] ?? '');

// Debug: Log received data
error_log("Received POST data: " . print_r($_POST, true));

// Validate form data
if (empty($subject) || empty($purpose)) {
    echo json_encode(['success' => false, 'suggestion' => 'Subject and purpose are required.']);
    exit();
}

// Fetch the user's department
$stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'suggestion' => 'User not found.']);
    exit();
}

$department_id = $user['department_id'];

// Debug: Log department ID
error_log("Department ID: " . $department_id);

// Fetch the first available storage location in the user's department
$stmt = $pdo->prepare("
    SELECT sl.*, c.cabinet_name 
    FROM storage_locations sl
    JOIN cabinets c ON sl.cabinet = c.id
    WHERE sl.department_id = ? AND sl.is_occupied = 0
    LIMIT 1
");
$stmt->execute([$department_id]);
$location = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug: Log the fetched location
error_log("Fetched Location: " . print_r($location, true));

if ($location) {
    // Mark the folder as occupied
    $stmt = $pdo->prepare("UPDATE storage_locations SET is_occupied = 1 WHERE id = ?");
    $stmt->execute([$location['id']]);

    echo json_encode([
        'success' => true,
        'suggestion' => "Store in Cabinet {$location['cabinet_name']}, Layer {$location['layer']}, Box {$location['box']}, Folder {$location['folder']}"
    ]);
} else {
    echo json_encode([
        'success' => false,
        'suggestion' => 'No available storage location found.'
    ]);
}

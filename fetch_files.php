<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch files for the logged-in user
$sql = "SELECT file_name FROM files WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$files = [];
while ($row = $result->fetch_assoc()) {
    $files[] = $row['file_name'];
}

$stmt->close();
$conn->close();

echo json_encode($files);
?>
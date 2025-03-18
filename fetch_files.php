<?php
require 'db_connection.php';

session_start();
$userId = $_SESSION['user_id'];
$departmentId = $_SESSION['department_id'];

// Fetch files for the user's folder
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ?");
$stmt->execute([$userId]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch files for the department folder
$stmt = $pdo->prepare("SELECT * FROM files WHERE department_id = ?");
$stmt->execute([$departmentId]);
$departmentFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['files' => $files, 'departmentFiles' => $departmentFiles]);

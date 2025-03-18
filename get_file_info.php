<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$fileId = $_GET['file_id'];

$stmt = $pdo->prepare("SELECT files.*, users.full_name, departments.name AS department_name 
                       FROM files 
                       JOIN users ON files.user_id = users.id 
                       LEFT JOIN departments ON users.department_id = departments.id 
                       WHERE files.id = ?");
$stmt->execute([$fileId]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($file);

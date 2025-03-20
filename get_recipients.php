<?php
require 'db_connection.php';

$query = $_GET['q'];

// Fetch users
$stmt = $pdo->prepare("SELECT id, username, 'user' AS type FROM users WHERE username LIKE ?");
$stmt->execute(["%$query%"]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch departments
$stmt = $pdo->prepare("SELECT id, name, 'department' AS type FROM departments WHERE name LIKE ?");
$stmt->execute(["%$query%"]);
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Combine results
$results = array_merge($users, $departments);

echo json_encode($results);

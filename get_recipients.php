<?php
include 'db_connection.php';

$searchTerm = isset($_GET['q']) ? $_GET['q'] : '';

// Fetch matching departments
$sqlDepartments = "SELECT id, name FROM departments WHERE name LIKE CONCAT('%', ?, '%')";
$stmtDepartments = $conn->prepare($sqlDepartments);
$stmtDepartments->bind_param("s", $searchTerm);
$stmtDepartments->execute();
$resultDepartments = $stmtDepartments->get_result();

$departments = [];
while ($row = $resultDepartments->fetch_assoc()) {
    $departments[] = ['id' => $row['id'], 'name' => $row['name']];
}

// Fetch matching users
$sqlUsers = "SELECT id, username FROM users WHERE username LIKE CONCAT('%', ?, '%')";
$stmtUsers = $conn->prepare($sqlUsers);
$stmtUsers->bind_param("s", $searchTerm);
$stmtUsers->execute();
$resultUsers = $stmtUsers->get_result();

$users = [];
while ($row = $resultUsers->fetch_assoc()) {
    $users[] = ['id' => $row['id'], 'username' => $row['username']];
}

// Return as JSON response
header('Content-Type: application/json');
echo json_encode(['departments' => $departments, 'users' => $users]);
$stmtDepartments->close();
$stmtUsers->close();
$conn->close();

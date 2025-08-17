<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_GET['user_id']) || !isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT User_id AS id, Username AS username, Position AS position, Role AS role, Profile_pic AS profile_pic
        FROM users WHERE User_id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT Department_id AS department_id
        FROM users_department WHERE User_id = ?
    ");
    $stmt->execute([$user_id]);
    $departments = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'departments' => $departments
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in get_user_data.php: " . $e->getMessage(), 3, __DIR__ . '/logs/error_log.log');
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>
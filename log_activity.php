<?php
require 'db_connection.php';

function logActivity($userId, $action)
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?, ?)");
    $stmt->execute([$userId, $action]);
}

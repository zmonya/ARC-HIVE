<?php
require 'db_connection.php';

function sendNotification($userId, $message)
{
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message) VALUES (?, 'info', ?)");
    $stmt->execute([$userId, $message]);
}

<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Unauthorized access.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['table'])) {
    $table = $_POST['table'];
    $stmt = $pdo->prepare("SELECT * FROM $table");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) > 0) {
        echo "<table>";
        echo "<thead><tr>";
        foreach (array_keys($data[0]) as $column) {
            echo "<th>" . htmlspecialchars($column) . "</th>";
        }
        echo "</tr></thead>";
        echo "<tbody>";
        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "No data found in the selected table.";
    }
} else {
    echo "Invalid request.";
}

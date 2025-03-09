<?php
$fileId = $_GET['fileId'];

$conn = new mysqli("localhost", "root", "", "document_archival");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the file details from the database
$stmt = $conn->prepare("SELECT file_name, department, building, office, shelf FROM files WHERE id = ?");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$stmt->bind_result($file_name, $department, $building, $office, $shelf);
$stmt->fetch();
$stmt->close();

// Insert the file into the recently_deleted table
$stmt = $conn->prepare("INSERT INTO recently_deleted (file_name, department, building, office, shelf) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $file_name, $department, $building, $office, $shelf);
if ($stmt->execute()) {
    // Delete the file from the files table
    $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to delete file from files table."]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Failed to move file to recently_deleted table."]);
}

$stmt->close();
$conn->close();

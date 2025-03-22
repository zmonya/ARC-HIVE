<?php
$fileId = $_GET['fileId'];
$newName = $_GET['newName'];

$conn = new mysqli("localhost", "root", "", "document_archival");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the old file name
$stmt = $conn->prepare("SELECT file_name FROM files WHERE id = ?");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$stmt->bind_result($oldFileName);
$stmt->fetch();
$stmt->close();

$oldFilePath = "uploads/" . $oldFileName;
$newFilePath = "uploads/" . $newName;

// Check if the file exists before renaming
if (file_exists($oldFilePath)) {
    if (rename($oldFilePath, $newFilePath)) {
        // Update the file name in the database
        $stmt = $conn->prepare("UPDATE files SET file_name = ? WHERE id = ?");
        $stmt->bind_param("si", $newName, $fileId);
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "error" => "File rename failed"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "File not found"]);
}

$conn->close();

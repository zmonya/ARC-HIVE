<?php
$fileId = $_GET['fileId'];

$conn = new mysqli("localhost", "root", "", "document_archival");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch the file details
$stmt = $conn->prepare("SELECT file_name, department, building, office, shelf FROM files WHERE id = ?");
$stmt->bind_param("i", $fileId);
$stmt->execute();
$stmt->bind_result($file_name, $department, $building, $office, $shelf);
$stmt->fetch();
$stmt->close();

$oldFilePath = "uploads/" . $file_name;
$newFileName = "Copy_of_" . $file_name;
$newFilePath = "uploads/" . $newFileName;

// Check if the original file exists before copying
if (file_exists($oldFilePath)) {
    if (copy($oldFilePath, $newFilePath)) {
        // Insert new record in the database with the new file name
        $stmt = $conn->prepare("INSERT INTO files (file_name, department, building, office, shelf) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $newFileName, $department, $building, $office, $shelf);
        if ($stmt->execute()) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "error" => "File copy failed"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Original file not found"]);
}

$conn->close();

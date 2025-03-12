<?php
// download.php
$conn = new mysqli("localhost", "root", "", "document_archival");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['fileId'])) {
    $fileId = $_GET['fileId'];

    // Fetch the file details from the database
    $stmt = $conn->prepare("SELECT file_name FROM files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->bind_result($fileName);
    $stmt->fetch();
    $stmt->close();

    if ($fileName) {
        // Determine the file path (assuming files are stored in a directory named "uploads")
        $filePath = "uploads/" . $fileName;

        // Check if the file exists
        if (file_exists($filePath)) {
            // Set headers for file download
            header("Content-Description: File Transfer");
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=" . basename($filePath));
            header("Expires: 0");
            header("Cache-Control: must-revalidate");
            header("Pragma: public");
            header("Content-Length: " . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            echo "File not found.";
        }
    } else {
        echo "File not found in the database.";
    }
} else {
    echo "File ID not provided.";
}

$conn->close();

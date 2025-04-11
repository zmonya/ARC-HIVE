<?php
$conn = new mysqli("localhost", "root", "", "document_archival");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['fileId'])) {
    $fileId = $_GET['fileId'];

    // Fetch file details from the database
    $stmt = $conn->prepare("SELECT file_name FROM files WHERE id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $stmt->bind_result($fileName);
    $stmt->fetch();
    $stmt->close();

    if ($fileName) {
        $filePath = "uploads/" . $fileName; // Adjust this if necessary

        if (file_exists($filePath)) {
            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

            // Define content types
            $mimeTypes = [
                "pdf" => "application/pdf",
                "docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                "doc" => "application/msword",
                "xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
                "xls" => "application/vnd.ms-excel",
                "txt" => "text/plain",
                "jpg" => "image/jpeg",
                "jpeg" => "image/jpeg",
                "png" => "image/png",
                "gif" => "image/gif"
            ];

            if (array_key_exists($fileExtension, $mimeTypes)) {
                header("Content-Type: " . $mimeTypes[$fileExtension]);
                header("Content-Disposition: inline; filename=" . basename($filePath));
                readfile($filePath);
                exit;
            } else {
                echo "Unsupported file type.";
            }
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

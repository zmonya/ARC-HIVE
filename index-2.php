<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Fetch user details with position and department
$stmt = $pdo->prepare("SELECT users.*, departments.name AS department_name 
                       FROM users 
                       LEFT JOIN departments ON users.department_id = departments.id 
                       WHERE users.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <!-- Hardcopy Storage Suggestion Popup -->
    <div class="popup-questionnaire" id="hardcopyStoragePopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('hardcopyStoragePopup')">x</button>
        <h3>Hardcopy Storage Suggestion</h3>
        <form id="hardcopyStorageForm">
            <label for="subject">Subject:</label>
            <input type="text" id="subject" name="subject" required>
            <label for="purpose">Purpose:</label>
            <select id="purpose" name="purpose" required>
                <option value="">Select Purpose</option>
                <option value="Meeting Announcement">Meeting Announcement</option>
                <option value="Title Approval">Title Approval</option>
                <option value="Request Letter">Request Letter</option>
            </select>
            <button type="submit" class="submit-button">Get Storage Suggestion</button>
        </form>
        <div id="storageSuggestion"></div>
    </div>

    <!-- Hardcopy Storage Button -->
    <button id="hardcopyStorageButton">Recommend Storage</button>

    <script>
        // Open hardcopy storage popup
        document.getElementById('hardcopyStorageButton').addEventListener('click', function() {
            document.getElementById('hardcopyStoragePopup').style.display = 'block';
        });

        // Close popup
        function closePopup(popupId) {
            document.getElementById(popupId).style.display = 'none';
        }

        // Handle form submission
        document.getElementById('hardcopyStorageForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const subject = document.getElementById('subject').value.trim();
            const purpose = document.getElementById('purpose').value.trim();

            if (!subject || !purpose) {
                alert("Please fill in all fields.");
                return;
            }

            // Prepare form data
            const formData = new URLSearchParams();
            formData.append('subject', subject);
            formData.append('purpose', purpose);

            // Debug: Log form data
            console.log("Form Data:", {
                subject,
                purpose
            });

            // Fetch storage suggestion
            fetch('get_storage_suggestions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        document.getElementById('storageSuggestion').textContent = data.suggestion;
                        document.getElementById('storageSuggestion').style.color = 'green';
                    } else {
                        document.getElementById('storageSuggestion').textContent = data.suggestion || "No storage suggestion available.";
                        document.getElementById('storageSuggestion').style.color = 'red';
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    document.getElementById('storageSuggestion').textContent = "An error occurred while fetching storage suggestion.";
                    document.getElementById('storageSuggestion').style.color = 'red';
                });
        });
    </script>
</body>

</html>
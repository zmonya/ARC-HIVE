<?php
session_start();
require 'db_connection.php';

$error = ""; // Initialize error variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize inputs
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $password = trim(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING));
    $full_name = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_STRING));
    $position = trim(filter_input(INPUT_POST, 'position', FILTER_SANITIZE_STRING));
    $department_ids = $_POST['departments'] ?? []; // Get selected department IDs

    // Validate required fields
    if (empty($username) || empty($password) || empty($full_name) || empty($position) || empty($department_ids)) {
        $error = "All fields are required.";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Username already exists.";
        } else {
            // Handle profile picture upload
            $profile_pic = null; // Default profile picture if none is uploaded
            if (!empty($_POST['cropped_image'])) {
                $croppedImage = $_POST['cropped_image'];
                $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $croppedImage));

                // Validate image data
                if ($imageData === false) {
                    $error = "Invalid image data.";
                } else {
                    // Save the cropped image to the server
                    $target_dir = "uploads/";
                    if (!is_dir($target_dir)) {
                        mkdir($target_dir, 0755, true); // Create the directory if it doesn't exist
                    }
                    $filename = uniqid() . '.png'; // Generate a unique filename
                    $target_file = $target_dir . $filename;

                    if (file_put_contents($target_file, $imageData)) {
                        $profile_pic = $target_file;
                    } else {
                        $error = "Failed to save cropped image.";
                    }
                }
            }

            if (empty($error)) {
                // Hash the password
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert new user into the database
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, position, profile_pic) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$username, $hashedPassword, $full_name, $position, $profile_pic])) {
                    // Get the last inserted user ID
                    $user_id = $pdo->lastInsertId();

                    // Create a folder for the user
                    $user_folder = "user_" . $username;
                    if (!is_dir($user_folder)) {
                        if (!mkdir($user_folder, 0755, true)) {
                            $error = "Failed to create user folder.";
                        }
                    }

                    // Insert user-department relationships
                    foreach ($department_ids as $department_id) {
                        $dept_stmt = $pdo->prepare("INSERT INTO user_departments (user_id, department_id) VALUES (?, ?)");
                        $dept_stmt->execute([$user_id, $department_id]);
                    }
                    
                    if (empty($error)) {
                        header("Location: login.php");
                        exit();
                    }
                } else {
                    $error = "Registration failed. Please try again.";
                }
            }
        }
    }
}

// Fetch departments for the checkboxes
$stmt = $pdo->prepare("SELECT id, name FROM departments");
$stmt->execute();
$departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <style>
        /* Global Styles */
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        /* Register Container */
        .register-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }

        .register-container h2 {
            margin-bottom: 20px;
            color: #34495e;
        }

        .register-container input,
        .register-container select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }

        .register-container button {
            width: 100%;
            padding: 10px;
            background: #34495e;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .register-container button:hover {
            background: #50c878;
        }

        .register-container p {
            margin-top: 15px;
            font-size: 14px;
        }

        .register-container a {
            color: #34495e;
            text-decoration: none;
            font-weight: bold;
        }

        .register-container a:hover {
            color: #50c878;
        }

        .error {
            color: #cc0000;
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Profile Picture Upload */
        .profile-pic-upload {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-pic-upload img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ccc;
        }

        .profile-pic-upload input[type="file"] {
            display: none;
        }

        .profile-pic-upload label {
            cursor: pointer;
            background: #34495e;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            margin-top: 10px;
        }

        .profile-pic-upload label:hover {
            background: #50c878;
        }

        /* Department Selection Styles */
        .department-selection {
            margin: 10px 0;
            text-align: left;
        }

        .department-selection .department-tag {
            background-color: #50c878;
            color: white;
            padding: 5px 10px;
            margin: 2px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
        }

        .department-selection .department-tag span {
            margin-left: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        /* Cropper Popup */
        .cropper-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .cropper-container {
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: #444;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .cropper-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .cropper-container button {
            margin-top: 10px;
            padding: 12px 20px;
            background: #50c878;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s, transform 0.2s;
        }

        .cropper-container button:hover {
            background: #40a867;
            transform: translateY(-2px);
        }

        /* Close Button */
        .close-button {
            background: transparent;
            border: none;
            color: #444;
            font-size: 20px;
            cursor: pointer;
            position: absolute;
            top: 15px;
            right: 15px;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <h2>Register</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form id="registration-form" action="register.php" method="POST" enctype="multipart/form-data">
            <div class="profile-pic-upload">
                <img id="profile-pic-preview" src="placeholder.jpg" alt="Profile Picture">
                <input type="file" name="profile_pic" id="profile-pic-input" accept="image/*">
                <label for="profile-pic-input">Upload Profile Picture</label>
            </div>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="text" name="position" placeholder="Position" required>

            <!-- Department Selection -->
            <div class="department-selection">
                <label>Recipients:</label>
                <div id="selected-departments"></div>
                <input type="text" id="department-search" placeholder="Type to search for departments..." oninput="filterDepartments()">
                <div id="department-list">
                    <?php foreach ($departments as $dept): ?>
                        <div class="department-item" data-id="<?= htmlspecialchars($dept['id']) ?>" style="display: block;">
                            <?= htmlspecialchars($dept['name']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <!-- Cropper.js Popup -->
    <div class="cropper-popup">
        <div class="cropper-container">
            <img id="cropper-image" src="" alt="Cropper Image">
            <button id="crop-button">Crop</button>
            <button id="cancel-button">Cancel</button>
        </div>
    </div>

    <!-- Cropper.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        const profilePicInput = document.getElementById('profile-pic-input');
        const profilePicPreview = document.getElementById('profile-pic-preview');
        const cropperPopup = document.querySelector('.cropper-popup');
        const cropperImage = document.getElementById('cropper-image');
        const cropButton = document.getElementById('crop-button');
        const cancelButton = document.getElementById('cancel-button');
        let cropper;

        profilePicInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    cropperPopup.style.display = 'flex';
                    cropperImage.src = e.target.result;

                    // Initialize Cropper.js
                    cropper = new Cropper(cropperImage, {
                        aspectRatio: 1, // 1:1 aspect ratio
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                        restore: true,
                        modal: true,
                        guides: true,
                        center: true,
                        highlight: true,
                        cropBoxResizable: true,
                        cropBoxMovable: true,
                    });
                };
                reader.readAsDataURL(file);
            }
        });

        // Crop the image
        cropButton.addEventListener('click', function() {
            const croppedCanvas = cropper.getCroppedCanvas({
                width: 200,
                height: 200,
            });

            // Update the profile picture preview
            profilePicPreview.src = croppedCanvas.toDataURL();

            // Hide the cropper popup
            cropperPopup.style.display = 'none';

            // Destroy the cropper instance
            cropper.destroy();
        });

        // Cancel cropping
        cancelButton.addEventListener('click', function() {
            cropperPopup.style.display = 'none';
            cropper.destroy();
        });

        // Department Selection Logic
        const selectedDepartmentsDiv = document.getElementById('selected-departments');
        const departmentSearch = document.getElementById('department-search');
        const departmentItems = document.querySelectorAll('.department-item');

        departmentItems.forEach(item => {
            item.addEventListener('click', function() {
                const deptId = this.dataset.id;
                const deptName = this.textContent;

                // Create a tag for the selected department
                const tag = document.createElement('div');
                tag.className = 'department-tag';
                tag.textContent = `Department: ${deptName}`;

                // Create a span for removing the tag
                const removeSpan = document.createElement('span');
                removeSpan.textContent = '✖';
                removeSpan.onclick = function() {
                    selectedDepartmentsDiv.removeChild(tag);
                    item.style.display = 'block'; // Show the item again
                };

                tag.appendChild(removeSpan);
                selectedDepartmentsDiv.appendChild(tag);

                this.style.display = 'none'; // Hide the department from the list
            });
        });

        function filterDepartments() {
            const searchTerm = departmentSearch.value.toLowerCase();
            departmentItems.forEach(item => {
                const deptName = item.textContent.toLowerCase();
                item.style.display = deptName.includes(searchTerm) ? 'block' : 'none';
            });
        }
    </script>
</body>

</html>
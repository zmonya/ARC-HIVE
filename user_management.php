<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = ""; // Initialize error variable

// Handle form submission for adding/editing users
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'];
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $password = trim(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $full_name = trim(filter_input(INPUT_POST, 'full_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $position = trim(filter_input(INPUT_POST, 'position', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $department_id = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_NUMBER_INT));
    $role = trim(filter_input(INPUT_POST, 'role', FILTER_SANITIZE_FULL_SPECIAL_CHARS));

    // Validate required fields
    if (empty($username) || empty($full_name) || empty($position) || empty($department_id) || empty($role)) {
        $error = "All fields are required.";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $existingUser = $stmt->fetch();

        if ($action === 'add' && $existingUser) {
            $error = "Username already exists.";
        } else {
            // Handle profile picture upload
            $profile_pic = handleProfilePictureUpload($_POST['cropped_image']);

            if (empty($error)) {
                if ($action === 'add') {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, position, department_id, role, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $success = $stmt->execute([$username, $hashedPassword, $full_name, $position, $department_id, $role, $profile_pic]);
                } elseif ($action === 'edit') {
                    $user_id = $_POST['user_id'];
                    $stmt = $pdo->prepare("UPDATE users SET username = ?, full_name = ?, position = ?, department_id = ?, role = ?, profile_pic = COALESCE(?, profile_pic) WHERE id = ?");
                    $success = $stmt->execute([$username, $full_name, $position, $department_id, $role, $profile_pic, $user_id]);
                }

                if ($success) {
                    header("Location: user_management.php");
                    exit();
                } else {
                    $error = "Failed to " . ($action === 'add' ? "add" : "update") . " user.";
                }
            }
        }
    }
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$user_id])) {
        header("Location: user_management.php");
        exit();
    } else {
        $error = "Failed to delete user.";
    }
}

// Fetch users and departments
$users = fetchAllUsers($pdo);
$departments = fetchAllDepartments($pdo);

function handleProfilePictureUpload($croppedImage)
{
    if (empty($croppedImage)) {
        return null;
    }

    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $croppedImage));
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $filename = uniqid() . '.png';
    $target_file = $target_dir . $filename;

    return file_put_contents($target_file, $imageData) ? $target_file : null;
}

function fetchAllUsers($pdo)
{
    $stmt = $pdo->prepare("SELECT users.*, departments.name AS department_name FROM users LEFT JOIN departments ON users.department_id = departments.id");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchAllDepartments($pdo)
{
    $stmt = $pdo->prepare("SELECT id, name FROM departments");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Global Styles */
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
            display: flex;
            /* Use flexbox for layout */
        }

        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            /* Fixed width for the sidebar */
            background: linear-gradient(220deg, #50c878, #34495e);
            height: 100vh;
            /* Full height */
            padding: 20px;
            color: white;
            display: flex;
            flex-direction: column;
            position: fixed;
            /* Fixed position */
            top: 0;
            /* Align to the top */
            left: 0;
            /* Align to the left */
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            z-index: 5;
            /* Lower z-index than topbar */
            transition: width 0.3s ease;
            /* Smooth transition for width */
        }

        .sidebar.minimized {
            width: 60px;
            /* Width when minimized */
        }

        .sidebar h2 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 300px;
            /* Same width as sidebar */
            padding: 20px;
            /* Padding for main content */
            transition: margin-left 0.3s;
            /* Smooth transition */
        }

        .main-content.resized {
            margin-left: 60px;
            /* Adjust for minimized state */
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 500px;
            position: relative;
        }

        .modal-content h2 {
            font-size: 24px;
            color: #34495e;
            margin-bottom: 20px;
            text-align: center;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #aaa;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #333;
        }

        /* Form Styles */
        .modal-content form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .modal-content input,
        .modal-content select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }

        .modal-content input:focus,
        .modal-content select:focus {
            border-color: #50c878;
        }

        .modal-content button[type="submit"] {
            padding: 12px;
            background-color: #34495e;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .modal-content button[type="submit"]:hover {
            background-color: #50c878;
        }

        /* Profile Picture Upload */
        .profile-pic-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .profile-pic-upload img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        .profile-pic-upload label {
            padding: 8px 15px;
            background-color: #34495e;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .profile-pic-upload label:hover {
            background-color: #50c878;
        }

        .profile-pic-upload input[type="file"] {
            display: none;
        }

        .close {
            color: #aaa;
            /* Light gray color */
            float: right;
            /* Align to the right */
            font-size: 28px;
            /* Font size */
            font-weight: bold;
            /* Bold font */
            cursor: pointer;
            /* Pointer cursor */
        }

        .close:hover,
        .close:focus {
            color: #000;
            /* Darker color on hover */
            text-decoration: none;
            /* No underline */
        }

        /* Form Styles */
        .form-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
            /* Space between form elements */
        }

        .form-container input,
        .form-container select {
            padding: 10px;
            /* Padding */
            border: 1px solid #ccc;
            /* Light gray border */
            border-radius: 6px;
            /* Rounded corners */
            font-size: 14px;
            /* Font size */
        }

        /* Button Styles */
        button {
            padding: 10px 20px;
            /* Padding */
            background: #34495e;
            /* Dark background */
            color: white;
            /* White text */
            border: none;
            /* No border */
            border-radius: 6px;
            /* Rounded corners */
            font-size: 16px;
            /* Font size */
            cursor: pointer;
            /* Pointer cursor */
            transition: background 0.3s;
            /* Smooth background transition */
        }

        button:hover {
            background: #50c878;
            /* Light green on hover */
        }

        /* Table Container */
        .table-container {
            margin-top: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        /* Toggle Buttons */
        .toggle-buttons {
            display: flex;
            justify-content: flex-start;
            gap: 10px;
            padding: 15px;
            background-color: #f9f9f9;
            border-bottom: 1px solid #ddd;
        }

        .toggle-buttons button {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
        }

        .toggle-buttons button.active {
            background-color: #34495e;
            color: white;
        }

        .toggle-buttons button:hover {
            background-color: #50c878;
            color: white;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #34495e;
            color: white;
            font-weight: 600;
        }

        tr:hover {
            background-color: #f5f5f5;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons button {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .action-buttons .edit-btn {
            background-color: #50c878;
            color: white;
        }

        .action-buttons .edit-btn:hover {
            background-color: #40a867;
        }

        .action-buttons .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .action-buttons .delete-btn:hover {
            background-color: #c82333;
        }

        /* Profile Picture in Table */
        td img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ddd;
        }

        /* Profile Picture Upload Styles */
        .profile-pic-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            /* Center items */
            margin-bottom: 15px;
            /* Space below */
        }

        .profile-pic-upload img {
            width: 100px;
            /* Width of image */
            height: 100px;
            /* Height of image */
            border-radius: 50%;
            /* Circular image */
            object-fit: cover;
            /* Cover the area */
            border: 2px solid #ccc;
            /* Light gray border */
        }

        .profile-pic-upload label {
            margin-top: 10px;
            /* Space above */
            padding: 5px 10px;
            /* Padding */
            background-color: #34495e;
            /* Dark background */
            color: white;
            /* White text */
            border-radius: 5px;
            /* Rounded corners */
            cursor: pointer;
            /* Pointer cursor */
            transition: background-color 0.3s;
            /* Smooth background transition */
        }

        .profile-pic-upload label:hover {
            background-color: #50c878;
            /* Light green on hover */
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

        /* Media Queries for Responsiveness */
        @media (max-width: 768px) {
            .topbar {
                padding: 10px 20px;
            }

            .topbar h1 {
                font-size: 20px;
            }

            .logout-btn {
                font-size: 14px;
                padding: 8px 12px;
            }

            .main-content {
                margin-left: 0;
                /* Adjust for smaller screens */
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <button class="toggle-btn" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="sidebar-title">Admin Panel</h2>
        <a href="admin_dashboard.php"><i class="fas fa-home"></i><span class="link-text"> Dashboard</span></a>
        <a href="user_management.php" class="active"><i class="fas fa-users"></i><span class="link-text"> User Management</span></a>
        <a href="department_management.php"><i class="fas fa-building"></i><span class="link-text"> Department Management</span></a>
        <a href="activity_logs.php"><i class="fas fa-history"></i><span class="link-text"> Activity Logs</span></a>
        <a href="physical_storage.php"><i class="fas fa-archive"></i><span class="link-text"> Physical Storage</span></a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span class="link-text"> Logout</span></a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Add/Edit User Form -->
        <button id="open-modal-btn" class="open-modal-btn">Add/Edit User</button>

        <!-- Popup Modal -->
        <div class="modal" id="user-modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2><?= isset($_GET['edit']) ? 'Edit User' : 'Add User' ?></h2>
                <form method="POST" action="user_management.php">
                    <input type="hidden" name="action" value="<?= isset($_GET['edit']) ? 'edit' : 'add' ?>">
                    <?php if (isset($_GET['edit'])): ?>
                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($_GET['edit']) ?>">
                    <?php endif; ?>
                    <div class="profile-pic-upload">
                        <img id="profile-pic-preview" src="placeholder.jpg" alt="Profile Picture">
                        <input type="file" name="profile_pic" id="profile-pic-input" accept="image/*">
                        <label for="profile-pic-input">Upload Profile Picture</label>
                    </div>
                    <input type="text" name="username" placeholder="Username" required>
                    <input type="password" name="password" placeholder="Password" <?= isset($_GET['edit']) ? '' : 'required' ?>>
                    <input type="text" name="full_name" placeholder="Full Name" required>
                    <input type="text" name="position" placeholder="Position" required>
                    <select name="department" required>
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['id']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="client">Client</option>
                    </select>
                    <input type="hidden" name="cropped_image" id="cropped-image-input">
                    <button type="submit"><?= isset($_GET['edit']) ? 'Update User' : 'Add User' ?></button>
                </form>
            </div>
        </div>


        <div class="table-container">
            <!-- Toggle Buttons -->
            <div class="toggle-buttons">
                <button id="toggle-all" class="active">All Users</button>
                <button id="toggle-admins">Admins</button>
                <button id="toggle-clients">Clients</button>
            </div>

            <!-- User Table -->
            <table id="user-table">
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr data-role="<?= htmlspecialchars($user['role']) ?>">
                            <td><img src="<?= htmlspecialchars($user['profile_pic'] ?? 'placeholder.jpg') ?>" alt="Profile"></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['position']) ?></td>
                            <td><?= htmlspecialchars($user['department_name']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td class="action-buttons">
                                <a href="user_management.php?edit=<?= $user['id'] ?>"><button class="edit-btn">Edit</button></a>
                                <a href="user_management.php?delete=<?= $user['id'] ?>" onclick="return confirm('Are you sure you want to delete this user?')"><button class="delete-btn">Delete</button></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cropper Popup -->
    <div class="cropper-popup">
        <div class="cropper-container">
            <img id="cropper-image" />
            <button id="crop-button">Crop Image</button>
            <button id="cancel-button">Cancel</button>
        </div>
    </div>


    <!-- Cropper.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
    <script>
        // JavaScript for handling the modal and cropper functionality
        const profilePicInput = document.getElementById('profile-pic-input');
        const profilePicPreview = document.getElementById('profile-pic-preview');
        const cropperPopup = document.querySelector('.cropper-popup');
        const cropperImage = document.getElementById('cropper-image');
        const cropButton = document.getElementById('crop-button');
        const cancelButton = document.getElementById('cancel-button');
        const croppedImageInput = document.getElementById('cropped-image-input');
        const toggleAll = document.getElementById('toggle-all');
        const toggleAdmins = document.getElementById('toggle-admins');
        const toggleClients = document.getElementById('toggle-clients');
        const userTable = document.getElementById('user-table').getElementsByTagName('tbody')[0].rows;
        let cropper;

        profilePicInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    cropperPopup.style.display = 'flex';
                    cropperImage.src = e.target.result;

                    // Initialize Cropper.js
                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(cropperImage, {
                        aspectRatio: 1, // 1:1 aspect ratio
                        viewMode: 1,
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

            // Set the cropped image data to the hidden input
            croppedImageInput.value = croppedCanvas.toDataURL();

            // Hide the cropper popup
            cropperPopup.style.display = 'none';

            // Destroy the cropper instance
            cropper.destroy();
            cropper = null; // Reset cropper variable
        });

        // Cancel cropping
        cancelButton.addEventListener('click', function() {
            cropperPopup.style.display = 'none';
            if (cropper) {
                cropper.destroy();
                cropper = null; // Reset cropper variable
            }
        });

        // Function to filter rows by role
        function filterTable(role) {
            for (let row of userTable) {
                const rowRole = row.getAttribute('data-role');
                if (role === 'all' || rowRole === role) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        // Event Listeners for Toggle Buttons
        toggleAll.addEventListener('click', () => {
            filterTable('all');
            toggleAll.classList.add('active');
            toggleAdmins.classList.remove('active');
            toggleClients.classList.remove('active');
        });

        toggleAdmins.addEventListener('click', () => {
            filterTable('admin');
            toggleAdmins.classList.add('active');
            toggleAll.classList.remove('active');
            toggleClients.classList.remove('active');
        });

        toggleClients.addEventListener('click', () => {
            filterTable('client');
            toggleClients.classList.add('active');
            toggleAll.classList.remove('active');
            toggleAdmins.classList.remove('active');
        });

        // Toggle sidebar and adjust topbar
        document.querySelector('.toggle-btn').addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');

            sidebar.classList.toggle('minimized');
            mainContent.classList.toggle('resized'); // Toggle resized class on main content
        });

        // Get the modal
        var modal = document.getElementById("user-modal");

        // Get the button that opens the modal
        var btn = document.getElementById("open-modal-btn");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>
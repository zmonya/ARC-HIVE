<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$error = ""; // Initialize error variable

// Handle form submission for adding/editing cabinets
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'];
    $cabinet_id = isset($_POST['cabinet_id']) ? $_POST['cabinet_id'] : null;
    $cabinet_name = trim(filter_input(INPUT_POST, 'cabinet_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $department_id = trim(filter_input(INPUT_POST, 'department', FILTER_SANITIZE_NUMBER_INT));
    $location = trim(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    $layers = trim(filter_input(INPUT_POST, 'layers', FILTER_SANITIZE_NUMBER_INT));
    $boxes = trim(filter_input(INPUT_POST, 'boxes', FILTER_SANITIZE_NUMBER_INT));
    $folders = trim(filter_input(INPUT_POST, 'folders', FILTER_SANITIZE_NUMBER_INT));

    // Validate required fields
    if (empty($cabinet_name) || empty($username) || empty($department_id) || empty($location) || empty($layers) || empty($boxes) || empty($folders)) {
        $error = "All fields are required.";
    } else {
        if ($action === 'add') {
            // Insert new cabinet
            $stmt = $pdo->prepare("INSERT INTO cabinets (cabinet_name, username, department_id, location, layers, boxes, folders) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([$cabinet_name, $username, $department_id, $location, $layers, $boxes, $folders]);

            // Create storage locations for the new cabinet
            if ($success) {
                $cabinet_id = $pdo->lastInsertId();
                for ($layer = 1; $layer <= $layers; $layer++) {
                    for ($box = 1; $box <= $boxes; $box++) {
                        for ($folder = 1; $folder <= $folders; $folder++) {
                            $stmt = $pdo->prepare("INSERT INTO storage_locations (cabinet, layer, box, folder, department_id, is_occupied) VALUES (?, ?, ?, ?, ?, 0)");
                            $stmt->execute([$cabinet_id, $layer, $box, $folder, $department_id]);
                        }
                    }
                }
            }
        } elseif ($action === 'edit') {
            // Update existing cabinet
            $stmt = $pdo->prepare("UPDATE cabinets SET cabinet_name = ?, username = ?, department_id = ?, location = ?, layers = ?, boxes = ?, folders = ? WHERE id = ?");
            $success = $stmt->execute([$cabinet_name, $username, $department_id, $location, $layers, $boxes, $folders, $cabinet_id]);
        }

        if ($success) {
            header("Location: physical_storage_management.php");
            exit();
        } else {
            $error = "Failed to " . ($action === 'add' ? "add" : "update") . " cabinet.";
        }
    }
}

// Handle cabinet deletion
if (isset($_GET['delete'])) {
    $cabinet_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM cabinets WHERE id = ?");
    if ($stmt->execute([$cabinet_id])) {
        // Delete associated storage locations
        $stmt = $pdo->prepare("DELETE FROM storage_locations WHERE cabinet = ?");
        $stmt->execute([$cabinet_id]);

        header("Location: physical_storage_management.php");
        exit();
    } else {
        $error = "Failed to delete cabinet.";
    }
}

// Handle storage location updates
if (isset($_POST['update_storage_location'])) {
    $location_id = $_POST['location_id'];
    $is_occupied = $_POST['is_occupied'];

    $stmt = $pdo->prepare("UPDATE storage_locations SET is_occupied = ? WHERE id = ?");
    if ($stmt->execute([$is_occupied, $location_id])) {
        header("Location: physical_storage_management.php");
        exit();
    } else {
        $error = "Failed to update storage location.";
    }
}

// Fetch cabinets and departments
$cabinets = fetchAllCabinets($pdo);
$departments = fetchAllDepartments($pdo);

function fetchAllCabinets($pdo)
{
    $stmt = $pdo->prepare("SELECT cabinets.*, departments.name AS department_name FROM cabinets LEFT JOIN departments ON cabinets.department_id = departments.id");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchAllDepartments($pdo)
{
    $stmt = $pdo->prepare("SELECT id, name FROM departments");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchStorageLocations($pdo, $cabinet_id)
{
    $stmt = $pdo->prepare("SELECT * FROM storage_locations WHERE cabinet = ?");
    $stmt->execute([$cabinet_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Physical Storage Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Masonry Grid System */
        .masonry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .cabinet-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .cabinet-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
        }

        .cabinet-card h3 {
            margin: 0 0 10px;
            font-size: 18px;
            color: #34495e;
        }

        .cabinet-card p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }

        .cabinet-card .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .cabinet-card .actions button {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .cabinet-card .actions .edit-btn {
            background-color: #50c878;
            color: white;
        }

        .cabinet-card .actions .edit-btn:hover {
            background-color: #40a867;
        }

        .cabinet-card .actions .delete-btn {
            background-color: #dc3545;
            color: white;
        }

        .cabinet-card .actions .delete-btn:hover {
            background-color: #c82333;
        }

        /* Storage Locations Table */
        .storage-locations-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .storage-locations-table th,
        .storage-locations-table td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .storage-locations-table th {
            background-color: #f9f9f9;
        }

        .storage-locations-table tr:hover {
            background-color: #f1f1f1;
        }

        .storage-locations-table .occupied {
            background-color: #ffcccc;
        }

        .storage-locations-table .available {
            background-color: #ccffcc;
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
        <a href="user_management.php"><i class="fas fa-users"></i><span class="link-text"> User Management</span></a>
        <a href="department_management.php"><i class="fas fa-building"></i><span class="link-text"> Department Management</span></a>
        <a href="activity_logs.php"><i class="fas fa-history"></i><span class="link-text"> Activity Logs</span></a>
        <a href="physical_storage_management.php" class="active"><i class="fas fa-archive"></i><span class="link-text"> Physical Storage</span></a>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i><span class="link-text"> Logout</span></a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Add/Edit Cabinet Form -->
        <button id="open-modal-btn" class="open-modal-btn">Add/Edit Cabinet</button>

        <!-- Toggle Buttons for Sorting -->
        <div class="toggle-buttons">
            <button id="toggle-all" class="active">All Cabinets</button>
            <button id="toggle-by-department">By Department</button>
            <button id="toggle-by-location">By Location</button>
        </div>

        <!-- Popup Modal -->
        <div class="modal" id="cabinet-modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2><?= isset($_GET['edit']) ? 'Edit Cabinet' : 'Add Cabinet' ?></h2>
                <form method="POST" action="physical_storage_management.php">
                    <input type="hidden" name="action" value="<?= isset($_GET['edit']) ? 'edit' : 'add' ?>">
                    <?php if (isset($_GET['edit'])): ?>
                        <input type="hidden" name="cabinet_id" value="<?= htmlspecialchars($_GET['edit']) ?>">
                    <?php endif; ?>
                    <div class="form-container">
                        <input type="text" name="cabinet_name" placeholder="Cabinet Name" required>
                        <input type="text" name="username" placeholder="Username" required>
                        <select name="department" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= htmlspecialchars($dept['id']) ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="location" placeholder="Location (Building & Room)" required>
                        <input type="number" name="layers" placeholder="Number of Layers" required>
                        <input type="number" name="boxes" placeholder="Number of Boxes" required>
                        <input type="number" name="folders" placeholder="Number of Folders" required>
                        <button type="submit"><?= isset($_GET['edit']) ? 'Update Cabinet' : 'Add Cabinet' ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Masonry Grid for Cabinets -->
        <div class="masonry-grid">
            <?php foreach ($cabinets as $cabinet): ?>
                <div class="cabinet-card" data-department="<?= htmlspecialchars($cabinet['department_name']) ?>" data-location="<?= htmlspecialchars($cabinet['location']) ?>">
                    <h3><?= htmlspecialchars($cabinet['cabinet_name']) ?></h3>
                    <p><strong>Department:</strong> <?= htmlspecialchars($cabinet['department_name']) ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($cabinet['location']) ?></p>
                    <p><strong>Layers:</strong> <?= htmlspecialchars($cabinet['layers']) ?></p>
                    <p><strong>Boxes:</strong> <?= htmlspecialchars($cabinet['boxes']) ?></p>
                    <p><strong>Folders:</strong> <?= htmlspecialchars($cabinet['folders']) ?></p>
                    <div class="actions">
                        <a href="physical_storage_management.php?edit=<?= $cabinet['id'] ?>"><button class="edit-btn">Edit</button></a>
                        <a href="physical_storage_management.php?delete=<?= $cabinet['id'] ?>" onclick="return confirm('Are you sure you want to delete this cabinet?')"><button class="delete-btn">Delete</button></a>
                    </div>

                    <!-- Display Storage Locations -->
                    <h4>Storage Locations</h4>
                    <table class="storage-locations-table">
                        <thead>
                            <tr>
                                <th>Layer</th>
                                <th>Box</th>
                                <th>Folder</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $storage_locations = fetchStorageLocations($pdo, $cabinet['id']);
                            foreach ($storage_locations as $location): ?>
                                <tr class="<?= $location['is_occupied'] ? 'occupied' : 'available' ?>">
                                    <td><?= htmlspecialchars($location['layer']) ?></td>
                                    <td><?= htmlspecialchars($location['box']) ?></td>
                                    <td><?= htmlspecialchars($location['folder']) ?></td>
                                    <td><?= $location['is_occupied'] ? 'Occupied' : 'Available' ?></td>
                                    <td>
                                        <form method="POST" action="physical_storage_management.php" style="display:inline;">
                                            <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                                            <input type="hidden" name="is_occupied" value="<?= $location['is_occupied'] ? 0 : 1 ?>">
                                            <button type="submit" name="update_storage_location" class="toggle-status-btn">
                                                <?= $location['is_occupied'] ? 'Mark as Available' : 'Mark as Occupied' ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        // JavaScript for handling the modal and sorting
        const modal = document.getElementById("cabinet-modal");
        const openModalBtn = document.getElementById("open-modal-btn");
        const closeModalBtn = document.querySelector(".close");

        openModalBtn.onclick = function() {
            modal.style.display = "block";
        }

        closeModalBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Sorting functionality
        const toggleAll = document.getElementById("toggle-all");
        const toggleByDepartment = document.getElementById("toggle-by-department");
        const toggleByLocation = document.getElementById("toggle-by-location");
        const cabinetCards = document.querySelectorAll(".cabinet-card");

        toggleAll.addEventListener("click", () => {
            cabinetCards.forEach(card => card.style.display = "block");
            toggleAll.classList.add("active");
            toggleByDepartment.classList.remove("active");
            toggleByLocation.classList.remove("active");
        });

        toggleByDepartment.addEventListener("click", () => {
            cabinetCards.forEach(card => {
                if (card.dataset.department === "Your Department") {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
            toggleByDepartment.classList.add("active");
            toggleAll.classList.remove("active");
            toggleByLocation.classList.remove("active");
        });

        toggleByLocation.addEventListener("click", () => {
            cabinetCards.forEach(card => {
                if (card.dataset.location === "Your Location") {
                    card.style.display = "block";
                } else {
                    card.style.display = "none";
                }
            });
            toggleByLocation.classList.add("active");
            toggleAll.classList.remove("active");
            toggleByDepartment.classList.remove("active");
        });
    </script>
</body>

</html>
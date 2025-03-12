<?php
session_start();
require 'db_connection.php';

// Redirect to login if not authenticated or not an admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: unauthorized.php'); // Redirect non-admin users
    exit();
}


$userId = $_SESSION['user_id'];

// Fetch admin details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch system statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalFiles = $pdo->query("SELECT COUNT(*) FROM files WHERE is_deleted = 0")->fetchColumn();
$pendingRequests = $pdo->query("SELECT COUNT(*) FROM access_requests WHERE status = 'pending'")->fetchColumn();

// Fetch file upload trends for the chart
$fileUploadTrends = $pdo->query("
    SELECT DATE(upload_date) AS upload_day, COUNT(*) AS upload_count 
    FROM files 
    GROUP BY upload_day 
    ORDER BY upload_day DESC 
    LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch storage usage by department
$storageUsage = $pdo->query("
    SELECT d.name AS department, SUM(f.file_size) AS total_size 
    FROM files f 
    JOIN departments d ON f.department_id = d.id 
    GROUP BY d.name
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch file categories distribution
$fileCategories = $pdo->query("
    SELECT file_type, COUNT(*) AS file_count 
    FROM files 
    GROUP BY file_type
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch access request status
$accessRequests = $pdo->query("
    SELECT status, COUNT(*) AS request_count 
    FROM access_requests 
    GROUP BY status
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch deleted files over time
$deletedFiles = $pdo->query("
    SELECT DATE(deleted_at) AS deleted_day, COUNT(*) AS deleted_count 
    FROM files 
    WHERE is_deleted = 1 
    GROUP BY deleted_day 
    ORDER BY deleted_day DESC 
    LIMIT 7
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Admin-specific styles */
        body {
            font-family: 'Montserrat', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f9;
        }

        .main-content {
            margin-left: 10px;
            padding: 10px;
        }

        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .stat-card h3 {
            margin-bottom: 10px;
            font-size: 18px;
            color: #34495e;
        }

        .stat-card p {
            font-size: 24px;
            font-weight: bold;
            color: #50c878;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .chart-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .chart-container h3 {
            color: #34495e;
            margin-bottom: 20px;
            font-size: 18px;
        }

        canvas {
            max-width: 100%;
            height: auto !important;
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2 class="sidebar-title">Admin Panel</h2>
        <a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i><span class="link-text"> Dashboard</span></a>
        <a href="user_management.php"><i class="fas fa-users"></i><span class="link-text"> User Management</span></a>
        <a href="department_management.php"><i class="fas fa-building"></i><span class="link-text"> Department Management</span></a>
        <a href="activity_logs.php"><i class="fas fa-history"></i><span class="link-text"> Activity Logs</span></a>
        <a href="physical_storage.php"><i class="fas fa-archive"></i><span class="link-text"> Physical Storage</span></a>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- System Statistics -->
        <div class="admin-stats">
            <div class="stat-card">
                <h3>Total Users</h3>
                <p><?= $totalUsers ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Files</h3>
                <p><?= $totalFiles ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Requests</h3>
                <p><?= $pendingRequests ?></p>
            </div>
        </div>

        <!-- Charts Grid -->
        <div class="chart-grid">
            <!-- File Upload Trends -->
            <div class="chart-container">
                <h3>File Upload Trends (Last 7 Days)</h3>
                <canvas id="fileUploadChart"></canvas>
            </div>

            <!-- Storage Usage by Department -->
            <div class="chart-container">
                <h3>Storage Usage by Department</h3>
                <canvas id="storageUsageChart"></canvas>
            </div>

            <!-- File Categories Distribution -->
            <div class="chart-container">
                <h3>File Categories Distribution</h3>
                <canvas id="fileCategoriesChart"></canvas>
            </div>

            <!-- Access Request Status -->
            <div class="chart-container">
                <h3>Access Request Status</h3>
                <canvas id="accessRequestsChart"></canvas>
            </div>

            <!-- Deleted Files Over Time -->
            <div class="chart-container">
                <h3>Deleted Files (Last 7 Days)</h3>
                <canvas id="deletedFilesChart"></canvas>
            </div>
        </div>
    </div>

    <script>
        // File Upload Trends Chart
        const fileUploadTrends = <?= json_encode($fileUploadTrends) ?>;
        const uploadLabels = fileUploadTrends.map(entry => entry.upload_day);
        const uploadData = fileUploadTrends.map(entry => entry.upload_count);

        new Chart(document.getElementById('fileUploadChart'), {
            type: 'line',
            data: {
                labels: uploadLabels.reverse(), // Reverse to show oldest to newest
                datasets: [{
                    label: 'File Uploads',
                    data: uploadData.reverse(), // Reverse to match labels
                    backgroundColor: 'rgba(80, 200, 120, 0.2)',
                    borderColor: '#50c878',
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Storage Usage by Department Chart
        const storageUsage = <?= json_encode($storageUsage) ?>;
        const storageLabels = storageUsage.map(entry => entry.department);
        const storageData = storageUsage.map(entry => entry.total_size / 1024); // Convert to MB

        new Chart(document.getElementById('storageUsageChart'), {
            type: 'bar',
            data: {
                labels: storageLabels,
                datasets: [{
                    label: 'Storage Usage (MB)',
                    data: storageData,
                    backgroundColor: 'rgba(80, 200, 120, 0.2)',
                    borderColor: '#50c878',
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // File Categories Distribution Chart
        const fileCategories = <?= json_encode($fileCategories) ?>;
        const categoryLabels = fileCategories.map(entry => entry.file_type);
        const categoryData = fileCategories.map(entry => entry.file_count);

        new Chart(document.getElementById('fileCategoriesChart'), {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    label: 'File Categories',
                    data: categoryData,
                    backgroundColor: [
                        '#50c878',
                        '#34495e',
                        '#dc3545',
                        '#ffc107',
                        '#17a2b8',
                        '#6610f2'
                    ]
                }]
            }
        });

        // Access Request Status Chart
        const accessRequests = <?= json_encode($accessRequests) ?>;
        const requestLabels = accessRequests.map(entry => entry.status);
        const requestData = accessRequests.map(entry => entry.request_count);

        new Chart(document.getElementById('accessRequestsChart'), {
            type: 'doughnut',
            data: {
                labels: requestLabels,
                datasets: [{
                    label: 'Access Requests',
                    data: requestData,
                    backgroundColor: [
                        '#50c878',
                        '#34495e',
                        '#dc3545'
                    ]
                }]
            }
        });

        // Deleted Files Over Time Chart
        const deletedFiles = <?= json_encode($deletedFiles) ?>;
        const deletedLabels = deletedFiles.map(entry => entry.deleted_day);
        const deletedData = deletedFiles.map(entry => entry.deleted_count);

        new Chart(document.getElementById('deletedFilesChart'), {
            type: 'line',
            data: {
                labels: deletedLabels.reverse(), // Reverse to show oldest to newest
                datasets: [{
                    label: 'Deleted Files',
                    data: deletedData.reverse(), // Reverse to match labels
                    backgroundColor: 'rgba(220, 53, 69, 0.2)',
                    borderColor: '#dc3545',
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>

</html>
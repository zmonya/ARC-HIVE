<?php
// backup.php
session_start();
require_once 'db_connection.php';
$pageTitle = 'System Backup';

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Redirect to login if not authenticated or not an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: unauthorized.php');
    exit();
}

$userId = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if ($userId === false) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Set timezone to Philippines (Tarlac)
date_default_timezone_set('Asia/Manila');

// Backup directory
$backupDir = 'backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Settings file
$settingsFile = 'config/backup_settings.json';
if (!is_dir('config')) {
    mkdir('config', 0777, true);
}

// Load or initialize settings
if (!file_exists($settingsFile)) {
    $defaultSettings = [
        'backup_hour' => 2,
        'backup_minute' => 0,
        'backup_date' => date('Y-m-d'), // Default to today
        'last_backup' => null
    ];
    file_put_contents($settingsFile, json_encode($defaultSettings, JSON_PRETTY_PRINT));
}
$settings = json_decode(file_get_contents($settingsFile), true);
$backup_hour = $settings['backup_hour'] ?? 2;
$backup_minute = $settings['backup_minute'] ?? 0;
$backup_date = $settings['backup_date'] ?? date('Y-m-d');

// Convert 24-hour format to 12-hour format with AM/PM for display
$display_hour = $backup_hour % 12 ?: 12;
$am_pm = $backup_hour >= 12 ? 'PM' : 'AM';

// Function to create database backup
function createBackup($pdo, $backupDir, $isAuto = false) {
    try {
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        
        $output = "-- Database Backup for water_dispenser_system\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Type: " . ($isAuto ? 'Automatic' : 'Manual') . "\n\n";

        foreach ($tables as $table) {
            $output .= "-- Table structure for $table\n\n";
            $createTable = $pdo->query("SHOW CREATE TABLE $table")->fetch();
            $output .= $createTable['Create Table'] . ";\n\n";

            $rows = $pdo->query("SELECT * FROM $table")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $output .= "-- Data for $table\n";
                foreach ($rows as $row) {
                    $values = array_map(function($value) use ($pdo) {
                        return $value === null ? 'NULL' : $pdo->quote($value);
                    }, array_values($row));
                    $output .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n";
                }
                $output .= "\n";
            }
        }

        $prefix = $isAuto ? 'auto_backup_' : 'manual_backup_';
        $filename = $backupDir . $prefix . date('Y-m-d_H-i-s') . '.sql';
        
        if (!file_put_contents($filename, $output)) {
            throw new Exception("Failed to write backup file");
        }
        
        // Implement retention policy: keep last 7 days of backups
        $retentionPeriod = 7 * 24 * 60 * 60;
        $backupFiles = glob($backupDir . '*.sql');
        foreach ($backupFiles as $file) {
            if (filemtime($file) < time() - $retentionPeriod) {
                unlink($file);
            }
        }
        
        return $filename;
    } catch (Exception $e) {
        error_log("Backup creation failed: " . $e->getMessage());
        throw $e;
    }
}

// Handle automatic backup check (server-side triggered via endpoint)
if (isset($_GET['check_auto_backup']) && $_GET['check_auto_backup'] == '1') {
    try {
        $backupFile = createBackup($pdo, $backupDir, true);
        $settings['last_backup'] = date('Y-m-d H:i:s');
        // Advance backup date to next day after successful backup
        $next_backup = new DateTime($backup_date);
        $next_backup->modify('+1 day');
        $settings['backup_date'] = $next_backup->format('Y-m-d');
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        // Trigger download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
        readfile($backupFile);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Automatic backup failed: ' . $e->getMessage()]);
        exit;
    }
}

// Handle manual backup creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['backup'])) {
    try {
        $backupFile = createBackup($pdo, $backupDir, false);
        $notification = 'success|Backup created successfully: ' . basename($backupFile);
        // Trigger download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($backupFile) . '"');
        readfile($backupFile);
        exit;
    } catch (Exception $e) {
        $notification = 'error|Backup failed: ' . $e->getMessage();
    }
}

// Handle update backup settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $backup_hour = intval($_POST['backup_hour']);
    $backup_minute = intval($_POST['backup_minute']);
    $am_pm_post = $_POST['am_pm'];
    $backup_date = $_POST['backup_date'];

    // Validate inputs
    if (!in_array($am_pm_post, ['AM', 'PM']) || $backup_hour < 1 || $backup_hour > 12 || $backup_minute < 0 || $backup_minute > 59 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $backup_date)) {
        $notification = 'error|Invalid input. Hour must be 1-12, Minute must be 0-59, AM/PM must be valid, Date must be YYYY-MM-DD.';
    } else {
        // Convert 12-hour format to 24-hour format
        $backup_hour_24 = $backup_hour;
        if ($am_pm_post === 'PM' && $backup_hour < 12) {
            $backup_hour_24 += 12;
        } elseif ($am_pm_post === 'AM' && $backup_hour == 12) {
            $backup_hour_24 = 0;
        }

        $settings['backup_hour'] = $backup_hour_24;
        $settings['backup_minute'] = $backup_minute;
        $settings['backup_date'] = $backup_date;
        $settings['last_backup'] = null; // Reset last backup to allow new backup
        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        $notification = 'success|Automatic backup settings updated successfully.';
        
        // Update display variables
        $display_hour = $backup_hour_24 % 12 ?: 12;
        $am_pm = $backup_hour_24 >= 12 ? 'PM' : 'AM';
    }
}

// Handle delete
$notification = isset($notification) ? $notification : '';
if (isset($_GET['delete'])) {
    $file = $backupDir . basename($_GET['delete']);
    if (file_exists($file)) {
        if (unlink($file)) {
            $notification = 'success|Backup deleted successfully: ' . basename($file);
        } else {
            $notification = 'error|Failed to delete backup: ' . basename($file);
        }
    } else {
        $notification = 'error|Backup file not found: ' . basename($file);
    }
}

// Check for message in URL
if (isset($_GET['message'])) {
    $notification = urldecode($_GET['message']);
}

// Get list of backups (sorted newest first)
$backupFiles = glob($backupDir . '*.sql');
rsort($backupFiles);

// Calculate next backup time for display
$next_backup = new DateTime($backup_date);
$next_backup->setTime($backup_hour, $backup_minute);
$now = new DateTime();
if ($next_backup <= $now) {
    $next_backup->modify('+1 day');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <title><?php echo $pageTitle; ?> - ArcHive</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-interface.css">
    <link rel="stylesheet" href="admin-sidebar.css">
</head>
<body class="admin-dashboard">
    <!-- Admin Sidebar -->
    <div class="sidebar">
        <button class="toggle-btn" title="Toggle Sidebar" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="sidebar-title">Admin Panel</h2>
        <a href="dashboard.php" class="client-btn">
            <i class="fas fa-exchange-alt"></i>
            <span class="link-text">Switch to Client View</span>
        </a>
        <a href="admin_dashboard.php">
            <i class="fas fa-home"></i>
            <span class="link-text">Dashboard</span>
        </a>
        <a href="admin_search.php">
            <i class="fas fa-search"></i>
            <span class="link-text">View All Files</span>
        </a>
        <a href="user_management.php">
            <i class="fas fa-users"></i>
            <span class="link-text">User Management</span>
        </a>
        <a href="department_management.php">
            <i class="fas fa-building"></i>
            <span class="link-text">Department Management</span>
        </a>
        <a href="physical_storage_management.php">
            <i class="fas fa-archive"></i>
            <span class="link-text">Physical Storage</span>
        </a>
        <a href="document_type_management.php">
            <i class="fas fa-file-alt"></i>
            <span class="link-text">Document Type Management</span>
        </a>
        <a href="backup.php" class="active">
            <i class="fas fa-file-alt"></i>
            <span class="link-text">System Backup</span>
        </a>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span class="link-text">Logout</span>
        </a>
    </div>

    <div class="main-content">
        <h2>System Backup</h2>
        <?php if ($notification): ?>
            <p class="notification-toast <?php echo explode('|', $notification)[0]; ?>">
                <?php echo htmlspecialchars(explode('|', $notification)[1], ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>
        
        <!-- Loading Indicator -->
        <div id="loadingIndicator" style="display: none;" class="notification-toast">
            Creating automatic backup...
        </div>

        <div class="content-area">
            <!-- Current Time Display -->
            <div class="current-time">
                <h3>Current Time (Asia/Manila)</h3>
                <p id="currentTime"><?php echo date('Y-m-d h:i:s A'); ?></p>
                <p>Next Scheduled Backup: <span id="nextBackup"><?php echo $next_backup->format('Y-m-d h:i A'); ?></span></p>
            </div>

            <!-- Manual backup form -->
            <form method="POST" id="backupForm" onsubmit="return confirmBackup();">
                <button type="submit" name="backup" class="btn-primary">
                    <i class="fas fa-plus"></i> Create Manual Backup
                </button>
            </form>

            <!-- Automatic backup settings -->
            <div class="backup-settings">
                <h3>Automatic Backup Settings</h3>
                <form method="POST" onsubmit="return confirmSettingsUpdate();">
                    <div class="input-group">
                        <label for="backup_date">Backup Date (Asia/Manila):</label>
                        <input type="date" id="backup_date" name="backup_date" value="<?php echo $backup_date; ?>" required>
                    </div>
                    <div class="input-group">
                        <label for="backup_hour">Backup Time (Asia/Manila):</label>
                        <div class="time-inputs">
                            <input type="number" id="backup_hour" name="backup_hour" min="1" max="12" value="<?php echo $display_hour; ?>" required placeholder="HH">
                            <span>:</span>
                            <input type="number" id="backup_minute" name="backup_minute" min="0" max="59" value="<?php printf("%02d", $backup_minute); ?>" required placeholder="MM">
                            <select name="am_pm" required>
                                <option value="AM" <?php echo $am_pm == 'AM' ? 'selected' : ''; ?>>AM</option>
                                <option value="PM" <?php echo $am_pm == 'PM' ? 'selected' : ''; ?>>PM</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_settings" class="btn-primary">Update Settings</button>
                </form>
            </div>

            <!-- List existing backups -->
            <div class="backup-list">
                <h3>Existing Backups</h3>
                <?php if (empty($backupFiles)): ?>
                    <p>No backups found.</p>
                <?php else: ?>
                    <div class="table-container">
                        <table class="data-table" id="backupsTable">
                            <thead>
                                <tr>
                                    <th>Filename</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="backupsTableBody">
                                <?php foreach ($backupFiles as $file): ?>
                                    <?php
                                        $filename = basename($file);
                                        $isAuto = strpos($filename, 'auto_backup_') === 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($filename, ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td class="backup-type-<?php echo $isAuto ? 'auto' : 'manual'; ?>">
                                            <?php echo $isAuto ? 'Automatic' : 'Manual'; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i:s', filemtime($file)); ?></td>
                                        <td class="action-buttons">
                                            <button class="btn-action download" onclick="window.location.href='<?php echo htmlspecialchars($file, ENT_QUOTES, 'UTF-8'); ?>'">
                                                <i class="fas fa-download"></i>
                                            </button>
                                            <button class="btn-action delete" onclick="if(confirm('Are you sure you want to delete this backup?')) window.location.href='backup.php?delete=<?php echo urlencode($filename); ?>'">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 250px;
            background-color: #2c3e50;
            overflow-y: auto;
            transition: width 0.3s ease;
            z-index: 1000;
        }

        .sidebar.minimized {
            width: 60px;
        }

        .main-content {
            margin-left: 290px;
            padding: 20px;
            flex: 1;
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        .main-content.sidebar-minimized {
            margin-left: 60px;
        }

        .content-area {
            width: 100%;
        }

        .content-header {
            margin-bottom: 20px;
        }

        h2 {
            font-size: 24px;
            color: #2c3e50;
            font-weight: 600;
            margin: 0 0 20px;
        }

        .notification-toast {
            font-weight: bold;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            width: fit-content;
            color: #fff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            z-index: 1100;
            animation: fadeOut 5s forwards;
        }

        .notification-toast.success {
            background-color: #d4edda;
            color: #28a745;
        }

        .notification-toast.error {
            background-color: #f8d7da;
            color: #dc3545;
        }

        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; display: none; }
        }

        .current-time {
            margin-bottom: 20px;
            padding: 15px;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .current-time h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .current-time p {
            color: #34495e;
            font-size: 16px;
        }

        .btn-primary {
            color: #fff;
            background-color: #28a745;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s, transform 0.1s;
        }

        .btn-primary:hover {
            background-color: #218838;
            transform: translateY(-1px);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .backup-settings {
            margin-top: 20px;
        }

        .backup-settings h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .backup-settings form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .backup-settings .input-group {
            display: flex;
            flex-direction: column;
        }

        .backup-settings .time-inputs {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .backup-settings label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .backup-settings input,
        .backup-settings select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100px;
        }

        .backup-settings input[type="date"] {
            width: 150px;
        }

        .backup-settings select {
            width: 100px;
        }

        .backup-settings input::-webkit-outer-spin-button,
        .backup-settings input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .backup-settings input[type=number] {
            -moz-appearance: textfield;
        }

        .backup-list {
            margin-top: 20px;
        }

        .backup-list h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .table-container {
            position: relative;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .data-table th {
            background-color: #3d3d3d;
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tr:hover {
            background-color: #f1f3f5;
        }

        .backup-type-auto {
            color: #28a745;
            font-weight: bold;
        }

        .backup-type-manual {
            color: #007bff;
            font-weight: bold;
        }

        .action-buttons button {
            margin-right: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }

        .btn-action.download {
            background-color: #007bff;
            color: white;
        }

        .btn-action.download:hover {
            background-color: #0056b3;
        }

        .btn-action.delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-action.delete:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 60px;
            }

            .main-content {
                margin-left: 60px;
            }

            .main-content.sidebar-expanded {
                margin-left: 290px;
            }

            .backup-settings input,
            .backup-settings select {
                width: 100%;
            }

            .time-inputs {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-primary {
                width: 100%;
                justify-content: center;
            }

            .data-table {
                font-size: 14px;
            }

            .data-table th,
            .data-table td {
                padding: 6px;
            }
        }
    </style>

    <script>
        // Update current time every second
        function updateTime() {
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                const now = new Date();
                const options = {
                    timeZone: 'Asia/Manila',
                    year: 'numeric',
                    month: 'numeric',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: true
                };
                timeElement.textContent = now.toLocaleString('en-PH', options);
            }
        }

        // Store scheduled time
        let scheduledTime = null;

        function setScheduledTime() {
            const dateInput = document.getElementById('backup_date').value;
            const hourInput = document.getElementById('backup_hour').value;
            const minuteInput = document.getElementById('backup_minute').value;
            const amPmInput = document.querySelector('select[name="am_pm"]').value;

            if (dateInput && hourInput && minuteInput && ['AM', 'PM'].includes(amPmInput)) {
                let hour = parseInt(hourInput);
                if (amPmInput === 'PM' && hour < 12) hour += 12;
                if (amPmInput === 'AM' && hour === 12) hour = 0;
                const scheduledDateTime = new Date(`${dateInput}T${hour.toString().padStart(2, '0')}:${minuteInput.padStart(2, '0')}:00`);
                
                // Ensure scheduled time is in the future
                const now = new Date();
                const nowInManila = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
                if (scheduledDateTime <= nowInManila) {
                    scheduledDateTime.setDate(scheduledDateTime.getDate() + 1);
                }
                
                scheduledTime = scheduledDateTime;
                
                // Update next backup display
                const nextBackup = document.getElementById('nextBackup');
                nextBackup.textContent = scheduledTime.toLocaleString('en-PH', {
                    timeZone: 'Asia/Manila',
                    year: 'numeric',
                    month: 'numeric',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }
        }

        function checkSchedule() {
            if (scheduledTime) {
                const now = new Date();
                const nowInManila = new Date(now.toLocaleString('en-US', { timeZone: 'Asia/Manila' }));
                const timeDiff = scheduledTime.getTime() - nowInManila.getTime();
                
                // Only trigger if the scheduled time is within 1 second and in the future
                if (timeDiff >= 0 && timeDiff <= 1000) {
                    const loadingIndicator = document.getElementById('loadingIndicator');
                    loadingIndicator.style.display = 'block';
                    
                    fetch('backup.php?check_auto_backup=1')
                        .then(response => {
                            if (response.headers.get('content-type')?.includes('application/octet-stream')) {
                                return response.blob().then(blob => {
                                    const url = window.URL.createObjectURL(blob);
                                    const a = document.createElement('a');
                                    a.href = url;
                                    a.download = response.headers.get('content-disposition')?.split('filename=')[1]?.replace(/"/g, '') || 'backup.sql';
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                    window.URL.revokeObjectURL(url);
                                    return { status: 'success', message: 'Automatic backup created and downloaded' };
                                });
                            }
                            return response.json();
                        })
                        .then(data => {
                            loadingIndicator.style.display = 'none';
                            const toast = document.createElement('div');
                            toast.className = `notification-toast ${data.status}`;
                            toast.textContent = data.message;
                            document.body.appendChild(toast);
                            setTimeout(() => {
                                toast.remove();
                            }, 5000);
                            
                            if (data.status === 'success') {
                                // Refresh backup list
                                fetchBackupList();
                                // Reset scheduled time to next day
                                scheduledTime.setDate(scheduledTime.getDate() + 1);
                                const nextBackup = document.getElementById('nextBackup');
                                nextBackup.textContent = scheduledTime.toLocaleString('en-PH', {
                                    timeZone: 'Asia/Manila',
                                    year: 'numeric',
                                    month: 'numeric',
                                    day: 'numeric',
                                    hour: 'numeric',
                                    minute: '2-digit',
                                    hour12: true
                                });
                            }
                        })
                        .catch(error => {
                            loadingIndicator.style.display = 'none';
                            const toast = document.createElement('div');
                            toast.className = 'notification-toast error';
                            toast.textContent = 'Error checking automatic backup: ' + error.message;
                            document.body.appendChild(toast);
                            setTimeout(() => {
                                toast.remove();
                            }, 5000);
                        });
                }
            }
        }

        function fetchBackupList() {
            fetch('backup.php')
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTableBody = doc.querySelector('#backupsTableBody');
                    if (newTableBody) {
                        const currentTableBody = document.querySelector('#backupsTableBody');
                        currentTableBody.innerHTML = newTableBody.innerHTML;
                    }
                });
        }

        function confirmBackup() {
            return confirm('Are you sure you want to create a new backup?');
        }

        function confirmSettingsUpdate() {
            const hour = document.getElementById('backup_hour').value;
            const minute = document.getElementById('backup_minute').value;
            const ampm = document.querySelector('select[name="am_pm"]').value;
            const date = document.getElementById('backup_date').value;
            
            if (hour < 1 || hour > 12 || minute < 0 || minute > 59 || !['AM', 'PM'].includes(ampm) || !/^\d{4}-\d{2}-\d{2}$/.test(date)) {
                alert('Invalid input. Please use hours 1-12, minutes 0-59, valid AM/PM, and date in YYYY-MM-DD format.');
                return false;
            }
            setScheduledTime();
            return confirm('Are you sure you want to update the automatic backup settings?');
        }

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            sidebar.classList.toggle('minimized');
            mainContent.classList.toggle('sidebar-minimized');
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide notification toast
            const toast = document.querySelector('.notification-toast');
            if (toast) {
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 5000);
            }
            
            // Initialize scheduled time
            setScheduledTime();
            
            // Update time every second
            updateTime();
            setInterval(updateTime, 1000);
            
            // Check schedule every second
            setInterval(checkSchedule, 1000);

            // Initialize sidebar state
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            mainContent.classList.add(sidebar.classList.contains('minimized') ? 'sidebar-minimized' : '');
        });
    </script>
</body>
</html>
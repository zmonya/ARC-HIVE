<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Include the CSS you provided here */
        /* Global Styles */
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        /* Top Navigation */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: calc(100% - 360px);
            background: linear-gradient(135deg, #50c878, #34495e);
            padding: 15px 30px;
            color: white;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            left: 300px;
            right: 50px;
            height: 60px;
            z-index: 10;
            backdrop-filter: blur(10px);
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(220deg, #50c878, #34495e);
            height: 100%;
            padding: 20px;
            color: white;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            padding-top: 100px;
            margin-left: 10px;
            margin-right: 10px;
            overflow-y: auto;
        }

        /* User ID and Calendar Container */
        .user-id-calendar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* User ID GUI */
        .user-id {
            display: flex;
            align-items: center;
        }

        .user-picture {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            margin-right: 15px;
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }

        .user-position,
        .user-department {
            font-size: 14px;
            margin: 2px 0;
            color: #555;
        }

        /* Digital Calendar and Clock */
        .digital-calendar-clock {
            text-align: right;
        }

        #currentDate,
        #currentTime {
            margin: 0;
            font-size: 14px;
            color: #333;
        }

        #currentDate {
            font-weight: bold;
        }

        /* Activity Log Dropdown */
        .activity-log {
            display: none; /* Hidden by default */
            position: absolute;
            top: 70px; /* Below the top navigation */
            right: 30px; /* Align with the right side */
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 300px; /* Set a width */
            z-index: 1000; /* Ensure it appears above other content */
        }

        .activity-log h3 {
            padding: 10px;
            margin: 0;
            background: #f5f6f7;
            border-bottom: 1px solid #ddd;
            color: #333;
            position: relative; /* For positioning the close button */
        }

        .activity-log .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            font-size: 16px;
            color: #aaa;
            transition: color 0.3s;
        }

        .activity-log .close-btn:hover {
            color: #d9534f; /* Change color on hover */
        }

        .log-entries {
            padding: 10px;
        }

        .log-entry {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            background: #fff;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .log-entry:hover {
            background: #f0f2f5;
        }

        .log-entry i {
            font-size: 16px;
            margin-right: 10px;
        }

        .log-entry p {
            margin: 0;
            flex: 1;
            font-size: 14px;
            color: #333;
        }

        .log-entry span {
            font-size: 12px;
            color: #606770;
        }

        /* Upload Document Section */
        .upload-document {
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 180px; /* Set a fixed width for the upload section */
            display: inline-block; /* Allow side-by-side layout */
        }

        .upload-document h3 {
            margin: 0 0 10px;
            color: #333;
        }

        .upload-document input[type="file"] {
            margin-bottom: 10px;
            width: 100%; /* Make the file input full width */
        }

        .upload-document button {
            padding: 10px 15px;
            background: #50c878;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .upload-document button:hover {
            background: #3da75b;
        }

        /* Notification Log Section */
        .notification-log {
            display: inline-block; /* Allow side-by-side layout */
            vertical-align: top; /* Align to the top */
            width: calc(100% - 270px); /* Adjust width based on upload section */
            margin-left: 20px; /* Space between upload and notification */
            max-height: 150px; /* Set max height to show only two notifications */
            overflow-y: auto; /* Enable vertical scrolling */
            position: relative; /* For positioning the header */
        }

        .notification-log h3 {
            position: sticky;
            top: 0; /* Stick to the top of the notification log */
            background: white; /* Background of the header */
            z-index: 1; /* Ensure it stays above the notifications */
            padding: 8px; /* Padding for the header */
            margin: 0; /* Remove default margin */
            border-bottom: 9px solid #ffff; /* Optional: border for better visibility */
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Document Archival</h2>
        <a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="users.php"><i class="fas fa-users"></i> Users</a>
        <a href="departments.php"><i class="fas fa-building"></i> Departments</a>
        <a href="files.php"><i class="fas fa-file"></i> Files</a>
        <a href="recently_deleted.php"><i class="fas fa-trash-alt"></i> Recently Deleted Files</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
    </div>

    <!-- Top Navigation -->
    <div class="top-nav">
        <h2>Admin Dashboard</h2>
        <input type="text" placeholder="Search documents...">
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <i class="fas fa-history activity-log-icon" onclick="toggleActivityLog()"></i>
    </div>

    <!-- Activity Log Dropdown -->
    <div class="activity-log" id="activityLog">
        <h3>Recent Activity <span class="close-btn" onclick="toggleActivityLog()">&times;</span></h3>
        <div class="log-entries">
            <div class="log-entry">
                <i class="fas fa-file-upload"></i>
                <p>Uploaded Document A</p>
                <span>09:30 AM</span>
            </div>
            <div class="log-entry">
                <i class="fas fa-file-download"></i>
                <p>Downloaded Document B</p>
                <span>09:15 AM</span>
            </div>
            <div class="log-entry">
                <i class="fas fa-trash"></i>
                <p>Deleted Document C</p>
                <span>08:45 AM</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- User ID and Calendar Section -->
        <div class="user-id-calendar-container">
            <!-- User ID GUI -->
            <div class="user-id">
                <img src="user1.jpg" alt="User Picture" class="user-picture">
                <div class="user-info">
                    <p class="user-name">Karl Patrick Mandapat</p>
                    <p class="user-position">Administrator</p>
                    <p class="user-department">College of Engineering and Technology</p>
                </div>
            </div>

            <!-- Digital Calendar and Clock -->
            <div class="digital-calendar-clock">
                <p id="currentDate"></p>
                <p id="currentTime"></p>
            </div>
        </div>

        <!-- Upload Document Section -->
        <div class="upload-document">
            <h3>Upload a Document</h3>
            <input type="file" id="fileUpload">
            <button onclick="uploadDocument()">Upload</button>
        </div>

        <!-- Notification Log Section -->
        <div class="notification-log">
            <h3>Notifications</h3>
            <div class="log-entries">
                <div class="log-entry">
                    <i class="fas fa-bell"></i>
                    <p>Document A has been shared with you</p>
                    <span>09:00 AM</span>
                </div>
                <div class="log-entry">
                    <i class="fas fa-info-circle"></i>
                    <p>Reminder: Review Document B</p>
                    <span>08:50 AM</span>
                </div>
                <div class="log-entry">
                    <i class="fas fa-user-plus"></i>
                    <p>New user added: User C</p>
                    <span>08:40 AM</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Function to update the clock and date
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const currentDate = now.toLocaleDateString('en-US', options);
            const currentTime = now.toLocaleTimeString('en-US');

            document.getElementById('currentDate').textContent = currentDate;
            document.getElementById('currentTime').textContent = currentTime;
        }

        // Update the clock every second
        setInterval(updateDateTime, 1000);
        updateDateTime(); // Initial call

        // Toggle activity log display
        function toggleActivityLog() {
            const activityLog = document.getElementById('activityLog');
            activityLog.style.display = activityLog.style.display === 'block' ? 'none' : 'block';
        }

        // Dummy function to handle document upload
        function uploadDocument() {
            alert('Document uploaded successfully!'); // Placeholder functionality
        }
    </script>
</body>
</html>

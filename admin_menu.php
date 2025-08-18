<!-- <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ArcHive</title>
    <link rel="stylesheet" href="style/admin-interface.css">
    <link rel="stylesheet" href="style/admin-sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
 -->
<body class="admin-dashboard">
    <!-- Admin Sidebar -->
    <div class="sidebar">
        <button class="toggle-btn" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="sidebar-title">Admin Panel</h2>
        <a href="dashboard.php" class="client-btn">
            <i class="fas fa-exchange-alt"></i>
            <span class="link-text">Switch to Client View</span>
        </a>
        <a href="admin_dashboard.php" class="active">
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
        <a href="backup.php">
            <i class="fas fa-file-alt"></i>
            <span class="link-text">System Backup</span>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span class="link-text">Logout</span>
            </a>
    </div>
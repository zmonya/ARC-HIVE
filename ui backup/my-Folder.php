<?php
session_start();
require 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user details with department name
$stmt = $pdo->prepare("SELECT users.*, departments.name AS department_name 
                       FROM users 
                       LEFT JOIN departments ON users.department_id = departments.id 
                       WHERE users.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch uploaded files by the user
$stmt = $pdo->prepare("SELECT * FROM files WHERE user_id = ? ORDER BY upload_date DESC");
$stmt->execute([$userId]);
$uploadedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch received files by the user
$stmt = $pdo->prepare("SELECT f.* FROM files f 
                       JOIN file_recipients fr ON f.id = fr.file_id 
                       WHERE fr.recipient_id = ? 
                       ORDER BY f.upload_date DESC");
$stmt->execute([$userId]);
$receivedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define the getFileIcon function
function getFileIcon($fileName)
{
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'pdf':
            return 'fas fa-file-pdf';
        case 'doc':
        case 'docx':
            return 'fas fa-file-word';
        case 'xls':
        case 'xlsx':
            return 'fas fa-file-excel';
        case 'jpg':
        case 'png':
            return 'fas fa-file-image';
        default:
            return 'fas fa-file';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Folder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* File Types Section */
        .file-types {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .file-type-card {
            flex: 1;
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 10px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .file-type-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .file-type-card i {
            font-size: 24px;
            color: #2c3e50;
        }

        .file-type-card p {
            margin: 10px 0 0;
            font-size: 14px;
            color: #333;
        }

        /* Masonry Grid Layout */
        .masonry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .masonry-section {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        .masonry-section h3 {
            margin-bottom: 15px;
            font-size: 18px;
            color: #2c3e50;
        }

        .file-card {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .file-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .file-card i {
            font-size: 24px;
            margin-right: 10px;
            color: #2c3e50;
        }

        .file-card p {
            margin: 0;
            font-size: 14px;
            color: #333;
        }

        .view-more {
            text-align: center;
            margin-top: 10px;
        }

        .view-more button {
            background: none;
            border: none;
            color: #3498db;
            cursor: pointer;
            font-size: 14px;
        }

        .view-more button:hover {
            text-decoration: underline;
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
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-content h2 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #2c3e50;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
        }

        .close-modal {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close-modal:hover {
            color: #ff4d4d;
        }

        /* File Options Styling */
        .file-options {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            z-index: 999;
        }

        .file-options .fa-ellipsis-v {
            font-size: 18px;
            color: #2c3e50;
            padding: 5px;
        }

        .options-menu {
            display: none;
            position: absolute;
            top: 25px;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .options-menu div {
            padding: 10px 20px;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            transition: background 0.3s;
        }

        .options-menu div:hover {
            background: #f0f0f0;
        }

        .options-menu.show {
            display: block;
        }

        /* File Info Sidebar */
        .file-info-sidebar {
            position: fixed;
            top: 0;
            right: -400px;
            width: 400px;
            height: 100%;
            background: white;
            box-shadow: -2px 0 5px rgba(0, 0, 0, 0.1);
            transition: right 0.3s ease;
            z-index: 1000;
        }

        .file-info-sidebar.active {
            right: 0;
        }

        .file-name-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #ddd;
        }

        .file-name {
            font-size: 18px;
            font-weight: bold;
        }

        .close-sidebar-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .close-sidebar-btn:hover {
            color: #ff4d4d;
        }

        .file-info-header {
            display: flex;
            justify-content: space-around;
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .file-info-header h4 {
            cursor: pointer;
            margin: 0;
            padding: 10px;
            transition: color 0.3s;
        }

        .file-info-header h4:hover {
            color: #3498db;
        }

        .info-section {
            padding: 20px;
            display: none;
        }

        .info-section.active {
            display: block;
        }

        .access-log {
            margin-bottom: 20px;
        }

        .access-users {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .access-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vertical-border {
            width: 1px;
            height: 20px;
            background: #ddd;
        }
    </style>
</head>

<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <h2><?= htmlspecialchars($user['full_name'] . "'s Folder"); ?></h2>
        <input type="text" placeholder="Search documents..." class="search-bar">
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <button class="toggle-btn" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="sidebar-title">Document Archival</h2>
        <a href="index.php"> <i class="fas fa-home"></i> Dashboard</a>
        <a href="my-folder.php" class="active"><i class="fas fa-folder"></i><span class="link-text"> My Folder</span></a>
        <?php if (!empty($user['department_id'])) : ?>
            <a href="department_folder.php">
                <i class="fas fa-folder"></i><span class="link-text"> <?= htmlspecialchars($user['department_name'] ?? 'No Department'); ?></span>
            </a>
        <?php endif; ?>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- File Types Section -->
        <div class="file-types">
            <?php
            $fileTypes = [
                'Word' => ['doc', 'docx'],
                'PDF' => ['pdf'],
                'Excel' => ['xls', 'xlsx'],
                'Images' => ['jpg', 'png']
            ];
            foreach ($fileTypes as $type => $extensions):
                // Combine uploaded and received files
                $allFiles = array_merge($uploadedFiles, $receivedFiles);
                // Filter files by type
                $filteredFiles = array_filter($allFiles, function ($file) use ($extensions) {
                    $fileExtension = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
                    return in_array($fileExtension, $extensions);
                });
                // Count the number of files for this type
                $fileCount = count($filteredFiles);
            ?>
                <div class="file-type-card" onclick="openModal('<?= strtolower($type) ?>')">
                    <i class="<?= getFileIcon('example.' . $extensions[0]) ?>"></i>
                    <p><?= $type ?> (<?= $fileCount ?>)</p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Masonry Grid Sections -->
        <div class="masonry-grid">
            <!-- Uploaded Files -->
            <div class="masonry-section">
                <h3>Uploaded Files</h3>
                <?php foreach (array_slice($uploadedFiles, 0, 4) as $file): ?>
                    <div class="file-card" onclick="openSidebar(<?= $file['id'] ?>)">
                        <i class="<?= getFileIcon($file['file_name']) ?>"></i>
                        <p><?= htmlspecialchars($file['file_name']) ?></p>
                        <div class="file-options" onclick="toggleOptions(this)">
                            <i class="fas fa-ellipsis-v"></i>
                            <div class="options-menu">
                                <div onclick="handleOption('Rename', <?= $file['id'] ?>)">Rename</div>
                                <div onclick="handleOption('Delete', <?= $file['id'] ?>)">Delete</div>
                                <div onclick="handleOption('Make Copy', <?= $file['id'] ?>)">Make Copy</div>
                                <div onclick="handleOption('File Information', <?= $file['id'] ?>)">File Information</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="view-more">
                    <button onclick="openModal('uploaded')">View More</button>
                </div>
            </div>

            <!-- Received Files -->
            <div class="masonry-section">
                <h3>Received Files</h3>
                <?php foreach (array_slice($receivedFiles, 0, 4) as $file): ?>
                    <div class="file-card" onclick="openSidebar(<?= $file['id'] ?>)">
                        <i class="<?= getFileIcon($file['file_name']) ?>"></i>
                        <p><?= htmlspecialchars($file['file_name']) ?></p>
                        <div class="file-options" onclick="toggleOptions(this)">
                            <i class="fas fa-ellipsis-v"></i>
                            <div class="options-menu">
                                <div onclick="handleOption('Rename', <?= $file['id'] ?>)">Rename</div>
                                <div onclick="handleOption('Delete', <?= $file['id'] ?>)">Delete</div>
                                <div onclick="handleOption('Make Copy', <?= $file['id'] ?>)">Make Copy</div>
                                <div onclick="handleOption('File Information', <?= $file['id'] ?>)">File Information</div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="view-more">
                    <button onclick="openModal('received')">View More</button>
                </div>
            </div>
        </div>
    </div>

    <!-- File Info Sidebar -->
    <div class="file-info-sidebar">
        <div class="file-name-container">
            <div class="file-name" id="sidebarFileName">File Name</div>
            <button class="close-sidebar-btn" onclick="closeSidebar()">&times;</button>
        </div>
        <div class="file-info-header">
            <div class="file-info-location active" onclick="showSection('locationSection')">
                <h4>Location</h4>
            </div>
            <div class="file-info-details" onclick="showSection('detailsSection')">
                <h4>Details</h4>
            </div>
        </div>
        <div class="info-section active" id="locationSection">
            <p><strong>Department:</strong> <span id="departmentCollege"><?= $user['department_name'] ?></span></p>
            <p><strong>Uploader:</strong> <span id="uploader"><?= $user['full_name'] ?></span></p>
            <p><strong>File Type:</strong> <span id="fileType"><?= $file['file_type'] ?></span></p>
            <p><strong>File Size:</strong> <span id="fileSize"><?= $file['file_size'] ?></span></p>
            <p><strong>Upload Date:</strong> <span id="dateUpload"><?= $file['upload_date'] ?></span></p>
        </div>
        <div class="info-section" id="detailsSection">
            <div class="access-log">
                <h3>Who Has Access</h3>
                <div class="access-users">
                    <img src="profile1.jpg" alt="User 1" class="profile-pic">
                    <img src="profile2.jpg" alt="User 2" class="profile-pic">
                    <img src="profile3.jpg" alt="User 3" class="profile-pic">
                </div>
                <p class="access-info">
                    <span class="current-access">Owned by you</span>
                    <span class="vertical-border"></span>
                    <span class="user-names">John, Alice, Mark</span>
                </p>
            </div>
            <h3>File Details</h3>
            <p>Uploader: <span id="uploader"><?= $user['full_name'] ?></span></p>
            <p>File Type: <span id="fileType"><?= $file['file_type'] ?></span></p>
            <p>File Size: <span id="fileSize"><?= $file['file_size'] ?></span></p>
            <p>File Category: <span id="fileCategory"><?= $file['file_category'] ?></span></p>
            <p>Date of Upload: <span id="dateUpload"><?= $file['upload_date'] ?></span></p>
            <p>Pages: <span id="pages"><?= $file['pages'] ?></span></p>
        </div>
    </div>

    <!-- Modals for File Types -->
    <?php foreach ($fileTypes as $type => $extensions): ?>
        <div id="<?= strtolower($type) ?>Modal" class="modal">
            <div class="modal-content">
                <button class="close-modal" onclick="closeModal('<?= strtolower($type) ?>')">&#10005;</button>
                <h2><?= $type ?> Files</h2>
                <div class="modal-grid">
                    <?php
                    // Combine uploaded and received files
                    $allFiles = array_merge($uploadedFiles, $receivedFiles);
                    foreach ($allFiles as $file):
                        if (in_array(pathinfo($file['file_name'], PATHINFO_EXTENSION), $extensions)): ?>
                            <div class="file-card" onclick="openSidebar(<?= $file['id'] ?>)">
                                <i class="<?= getFileIcon($file['file_name']) ?>"></i>
                                <p><?= htmlspecialchars($file['file_name']) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        // File Options Dropdown
        function toggleOptions(element) {
            document.querySelectorAll('.options-menu').forEach(menu => {
                if (menu !== element.nextElementSibling) {
                    menu.classList.remove('show');
                }
            });
            element.nextElementSibling.classList.toggle('show');
        }

        document.addEventListener('click', function(event) {
            if (!event.target.closest('.file-options')) {
                document.querySelectorAll('.options-menu').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        function handleOption(option, fileId) {
            if (option === 'File Information') {
                openSidebar(fileId);
            } else {
                alert(`Handling option: ${option} for file ID: ${fileId}`);
            }
        }

        // File Info Sidebar
        function openSidebar(fileId) {
            fetch(`get_file_info.php?file_id=${fileId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('sidebarFileName').textContent = data.file_name;
                    document.getElementById('departmentCollege').textContent = data.department_name;
                    document.getElementById('uploader').textContent = data.full_name;
                    document.getElementById('fileType').textContent = data.file_type;
                    document.getElementById('fileSize').textContent = data.file_size;
                    document.getElementById('dateUpload').textContent = data.upload_date;
                    document.getElementById('fileCategory').textContent = data.file_category;
                    document.getElementById('pages').textContent = data.pages;
                    document.querySelector('.file-info-sidebar').classList.add('active');
                });
        }

        function closeSidebar() {
            document.querySelector('.file-info-sidebar').classList.remove('active');
        }

        function showSection(sectionId) {
            document.querySelectorAll('.info-section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(sectionId).classList.add('active');
        }

        // Modals
        function openModal(type) {
            document.getElementById(`${type}Modal`).style.display = 'flex';
        }

        function closeModal(type) {
            document.getElementById(`${type}Modal`).style.display = 'none';
        }
    </script>
</body>

</html>
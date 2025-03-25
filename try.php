<?php
session_start();
require 'db_connection.php';

function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}

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
        case 'jpeg':
        case 'gif':
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
    <link rel="stylesheet" href="folders.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* Masonry Grid Layout */
        .masonry-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            /* Responsive grid */
            gap: 20px;
            margin: 20px 0;
            transition: grid-template-columns 0.3s ease;
            /* Smooth transition */
        }

        /* When sidebar is active */
        .masonry-grid.sidebar-active {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) 400px;
            /* Add sidebar column */
        }

        /* Masonry Section */
        .masonry-section {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            box-sizing: border-box;
        }

        /* File Info Sidebar */
        .file-info-sidebar {
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            overflow-y: auto;
            display: none;
            /* Hidden by default */
            grid-column: -1;
            /* Place sidebar in the last column */
        }

        .masonry-grid.sidebar-active .file-info-sidebar {
            display: block;
            /* Show sidebar when active */
        }

        /* File Name Container */
        .file-name-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .close-sidebar-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: color 0.2s;
        }

        .close-sidebar-btn:hover {
            color: #ff4d4d;
        }

        /* File Info Header */
        .file-info-header {
            display: flex;
            justify-content: space-around;
            padding: 10px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
        }

        .file-info-header h4 {
            cursor: pointer;
            margin: 0;
            padding: 10px;
            font-size: 16px;
            color: #666;
            transition: color 0.3s;
        }

        .file-info-header h4:hover {
            color: #3498db;
        }

        .file-info-header h4.active {
            color: #2c3e50;
            font-weight: 600;
            border-bottom: 2px solid #3498db;
        }

        /* Info Sections */
        .info-section {
            padding: 20px;
            display: none;
            flex-grow: 1;
            overflow-y: auto;
        }

        .info-section.active {
            display: block;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            font-weight: 600;
            color: #2c3e50;
            display: block;
            margin-bottom: 5px;
        }

        .info-value {
            color: #666;
            font-size: 14px;
        }

        /* Access Log */
        .access-log {
            margin-bottom: 20px;
        }

        .access-log h3 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 10px;
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
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .access-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
            font-size: 14px;
        }

        .vertical-border {
            width: 1px;
            height: 20px;
            background: #ddd;
        }

        /* File Details */
        .file-details h3 {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 15px;
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
                <div class="file-card-container">
                    <?php foreach (array_slice($uploadedFiles, 0, 4) as $file): ?>
                        <div class="file-card" onclick="openSidebar(<?= $file['id'] ?>)">
                            <!-- File Icon -->
                            <div class="file-icon-container">
                                <i class="<?= getFileIcon($file['file_name']) ?> file-icon"></i>
                            </div>
                            <!-- File Name -->
                            <p class="file-name"><?= htmlspecialchars($file['file_name']) ?></p>
                            <!-- File Options -->
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
                </div>
                <div class="view-more">
                    <button onclick="openModal('uploaded')">View More</button>
                </div>
            </div>

            <!-- Received Files -->
            <div class="masonry-section">
                <h3>Received Files</h3>
                <div class="file-card-container">
                    <?php foreach (array_slice($receivedFiles, 0, 4) as $file): ?>
                        <div class="file-card" onclick="openSidebar(<?= $file['id'] ?>)">
                            <!-- File Icon -->
                            <div class="file-icon-container">
                                <i class="<?= getFileIcon($file['file_name']) ?> file-icon"></i>
                            </div>
                            <!-- File Name -->
                            <p class="file-name"><?= htmlspecialchars($file['file_name']) ?></p>
                            <!-- File Options -->
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
                </div>
                <div class="view-more">
                    <button onclick="openModal('received')">View More</button>
                </div>
            </div>

            <!-- File Info Sidebar -->
            <div class="file-info-sidebar">
                <!-- File Name Container -->
                <div class="file-name-container">
                    <div class="file-name-title" id="sidebarFileName">File Name</div>
                    <button class="close-sidebar-btn" onclick="closeSidebar()">&times;</button>
                </div>

                <!-- File Info Header -->
                <div class="file-info-header">
                    <div class="file-info-location active" onclick="showSection('locationSection')">
                        <h4>Location</h4>
                    </div>
                    <div class="file-info-details" onclick="showSection('detailsSection')">
                        <h4>Details</h4>
                    </div>
                </div>

                <!-- Location Section -->
                <div class="info-section active" id="locationSection">
                    <div class="info-item">
                        <span class="info-label">Department:</span>
                        <span class="info-value" id="departmentCollege"><?= $user['department_name'] ?></span>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="info-section" id="detailsSection">
                    <!-- Access Log -->
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

                    <!-- File Details -->
                    <div class="file-details">
                        <h3>File Details</h3>
                        <div class="info-item">
                            <span class="info-label">Uploader:</span>
                            <span class="info-value" id="uploader"><?= $user['full_name'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">File Type:</span>
                            <span class="info-value" id="fileType"><?= $file['file_type'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">File Size:</span>
                            <span class="info-value" id="fileSize"><?= formatFileSize($file['file_size']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">File Category:</span>
                            <span class="info-value" id="fileCategory"><?= $file['file_category'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Upload:</span>
                            <span class="info-value" id="dateUpload"><?= $file['upload_date'] ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Pages:</span>
                            <span class="info-value" id="pages"><?= $file['pages'] ?></span>
                        </div>
                    </div>
                </div>
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
                                    <div class="file-icon-container">
                                        <i class="<?= getFileIcon($file['file_name']) ?> file-icon"></i>
                                    </div>
                                    <p class="file-name"><?= htmlspecialchars($file['file_name']) ?></p>
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
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <script>
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
                        document.querySelector('.masonry-grid').classList.add('sidebar-active');
                    });
            }

            function closeSidebar() {
                document.querySelector('.masonry-grid').classList.remove('sidebar-active');
            }

            function showSection(sectionId) {
                document.querySelectorAll('.info-section').forEach(section => {
                    section.classList.remove('active');
                });
                document.getElementById(sectionId).classList.add('active');
            }
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
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
$departmentId = $_SESSION['department_id'];

// Fetch user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch department details
$stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
$stmt->execute([$departmentId]);
$department = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$department) {
    die("Department not found.");
}

$departmentName = $department['name'];

// Check if the user belongs to the department they are trying to access
if ($user['department_id'] != $departmentId) {
    die("Access denied. You do not have permission to access this department's folder.");
}

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

// Fetch files shared with the department
$stmt = $pdo->prepare("
    SELECT f.* 
    FROM files f 
    LEFT JOIN file_departments fd ON f.id = fd.file_id 
    WHERE fd.department_id = :department_id 
    ORDER BY f.upload_date DESC
");
$stmt->bindValue(':department_id', $departmentId, PDO::PARAM_INT);
$stmt->execute();
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($departmentName); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="folders.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">

</head>

<body>
    <!-- Top Navigation -->
    <div class="top-nav">
        <h2><?= htmlspecialchars($departmentName); ?></h2>
        <input type="text" placeholder="Search documents..." class="search-bar">
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <button class="toggle-btn" title="Toggle Sidebar">
            <i class="fas fa-bars"></i>
        </button>
        <h2 class="sidebar-title">Document Archival</h2>
        <a href="index.php"> <i class="fas fa-home"></i> Dashboard</a>
        <a href="my-folder.php"><i class="fas fa-folder"></i> My Folder</a>
        <a href="department_folder.php" class="active"><i class="fas fa-folder"></i><?= htmlspecialchars($departmentName); ?></a>
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
                // Filter files by type
                $filteredFiles = array_filter($files, function ($file) use ($extensions) {
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
            <!-- Department Files -->
            <div class="masonry-section">
                <h3>Department Files</h3>
                <div class="file-card-container">
                    <?php foreach ($files as $file): ?>
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
                    <button onclick="openModal('department')">View More</button>
                </div>
            </div>
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
                <span class="info-value" id="departmentCollege"><?= $departmentName ?></span>
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

    <!-- Modals for File Types -->
    <?php foreach ($fileTypes as $type => $extensions): ?>
        <div id="<?= strtolower($type) ?>Modal" class="modal">
            <div class="modal-content">
                <button class="close-modal" onclick="closeModal('<?= strtolower($type) ?>')">&#10005;</button>
                <h2><?= $type ?> Files</h2>
                <div class="modal-grid">
                    <?php foreach ($files as $file): ?>
                        <?php if (in_array(pathinfo($file['file_name'], PATHINFO_EXTENSION), $extensions)): ?>
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
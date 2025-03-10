<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments</title>
    <style>
        /* Category Section */
        .category-section {
            margin-top: 30px;
        }

        .category-header {
            font-size: 22px;
            margin-bottom: 15px;
        }

        .category {
            display: flex;
            flex-wrap: wrap;
            /* Allow items to wrap into multiple rows */
            gap: 20px;
            margin-bottom: 30px;
        }

        .category-item {
            flex: 1 1 calc(25% - 20px);
            /* Adjust width for 4 items per row */
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .category-item:hover {
            transform: scale(1.05);
            cursor: pointer;
        }

        .category-item i {
            font-size: 30px;
            color: #2c3e50;
        }
    </style>
</head>

<body>
    <!-- Document Categories Section -->
    <div class="category-section">
        <div class="category-header">College and Offices</div>
        <div class="category">
            <div class="category-item">
                <i class="fas fa-seedling"></i>
                <p>College of Agriculture and Forestry</p>
            </div>
            <div class="category-item">
                <i class="fas fa-palette"></i>
                <p>College of Arts and Sciences</p>
            </div>
            <div class="category-item">
                <i class="fas fa-briefcase"></i>
                <p>College of Business and Management</p>
            </div>
            <div class="category-item">
                <i class="fas fa-graduation-cap"></i>
                <p>College of Education</p>
            </div>

            <div class="category-item">
                <a href="CET-folder.php" class="category-link">
                    <i class="fas fa-cogs"></i>
                    <p>College of Engineering and Technology</p>
                </a>
            </div>

            <div class="category-item">
                <i class="fas fa-paw"></i>
                <p>College of Veterinary Medicine</p>
            </div>
            <div class="category-item">
                <i class="fas fa-clipboard-list"></i>
                <p>Admission and Registration Services</p>
            </div>
            <div class="category-item">
                <i class="fas fa-file-invoice-dollar"></i>
                <p>Audit Offices</p>
            </div>
            <div class="category-item">
                <i class="fas fa-globe"></i>
                <p>External Linkages and International Affairs</p>
            </div>
            <div class="category-item">
                <i class="fas fa-server"></i>
                <p>Management Information Systems</p>
            </div>
            <div class="category-item">
                <i class="fas fa-user-tie"></i>
                <p>Office of the President</p>
            </div>
        </div>
    </div>
</body>

</html>
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Global Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 300px;
            right: 50px;
            height: 60px;
            z-index: 10;
        }

        .top-nav h2 {
            font-size: 20px;
            margin: 0;
        }

        .top-nav input {
            padding: 10px;
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 6px;
            transition: border-color 0.3s;
        }

        .top-nav input:focus {
            border-color: #3498db;
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

        .sidebar h2 {
            text-align: center;
            font-size: 20px;
            margin-bottom: 20px;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px;
            color: white;
            text-decoration: none;
            font-size: 16px;
            transition: background 0.3s;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .sidebar a i {
            margin-right: 12px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255, 255, 255, 0.2);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            padding-top: 50px;
            margin-top: 100px;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            padding: 15px 30px;
            color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .header h2 {
            font-size: 20px;
            margin: 0;
        }

        .header input {
            padding: 10px;
            width: 250px;
            border: 1px solid #ccc;
            border-radius: 6px;
            transition: border-color 0.3s;
        }

        .header input:focus {
            border-color: #3498db;
        }

        /* File Section */
        .file-section {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 30px;
        }

        .file {
            width: 220px;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(8px);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .file:hover {
            transform: scale(1.05);
        }

        .file i {
            font-size: 50px;
            color: #2c3e50;
        }

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
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .category-item {
            flex: 1;
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

        /* Upload Section */
        .upload-file,
        .file-upload-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 500px;
            margin: 20px auto;
        }

        .upload-file input[type="file"],
        .file-upload-section input[type="file"] {
            margin: 15px 0;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            width: 100%;
            box-sizing: border-box;
            font-size: 14px;
        }

        .upload-file button,
        .file-upload-section button {
            background: #2c3e50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
            width: 100%;
            max-width: 200px;
            margin-top: 15px;
        }

        .upload-file button:hover,
        .file-upload-section button:hover {
            background: #1a252f;
        }

        /* Popups */
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            width: 400px;
            text-align: center;
        }

        /* EXIT BOTTON*/
        .exit-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #333;
            padding: 5px;
        }

        .exit-button:hover {
            color: #cc0000;
            /* Change color on hover for better UX */
        }

        .popup h3 {
            margin-bottom: 20px;
        }

        .popup button {
            background: #2c3e50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
            transition: background 0.3s;
        }

        .popup button:hover {
            background: #1a252f;
        }

        .popup-questionnaire {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #f0f2f5;
            width: 450px;
            padding: 20px;
            border: 1px solid #34495e;
            border-radius: 6px;
            box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.3);
            font-family: Arial, sans-serif;
            text-align: center;
            display: none;
            z-index: 1000;
        }

        .popup-questionnaire h3 {
            color: #34495e;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .popup-button {
            background: #34495e;
            color: white;
            font-size: 14px;
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 4px;
            border: 1px solid #34495e;
            cursor: pointer;
        }

        .popup-button:hover {
            background: #50c878;
        }

        .popup-questionnaire form {
            text-align: left;
        }

        .popup-questionnaire label {
            display: block;
            font-weight: bold;
            color: #34495e;
            margin-top: 10px;
        }

        .popup-questionnaire input {
            width: 100%;
            padding: 5px;
            border: 1px solid #34495e;
            border-radius: 4px;
            font-size: 14px;
        }

        .submit-button {
            background: #34495e;
            color: white;
            font-size: 14px;
            font-weight: bold;
            padding: 8px 16px;
            border-radius: 4px;
            border: 1px solid #34495e;
            cursor: pointer;
            width: 100%;
            margin-top: 15px;
        }

        .submit-button:hover {
            background: #50c878;
        }

        .popup-error {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #ffebeb;
            width: 400px;
            padding: 15px;
            border: 1px solid #cc0000;
            border-radius: 6px;
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.3);
            text-align: left;
            font-family: Arial, sans-serif;
            display: none;
            z-index: 1000;
        }

        .popup-error h3 {
            color: #cc0000;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .popup-error p {
            color: #333;
            font-size: 14px;
            margin: 0;
            line-height: 1.5;
        }

        .popup-error .popup-button {
            display: inline-block;
            background: linear-gradient(to bottom, #dd4b39, #b22222);
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            border: 1px solid #8b0000;
            cursor: pointer;
            margin-top: 10px;
            text-align: center;
        }

        .popup-error .popup-button:hover {
            background: linear-gradient(to bottom, #b22222, #8b0000);
        }

        /* Right Sidebar */
        .upload-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        .recent-files {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 20px;
        }

        h3 {
            margin-bottom: 20px;
            font-size: 20px;
            color: #333;
            text-align: center;
        }

        .files-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            flex-wrap: wrap;
        }

        .file p {
            margin-top: 10px;
            font-size: 14px;
        }

        .arrow i.fas.fa-arrow-left {
            position: fixed;
            right: 0;
            top: 50%;
            transform: translateY(-50%);
            font-size: 30px;
            color: green;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .arrow i.fas.fa-arrow-left:hover {
            transform: scale(1.2) translateY(-50%);
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>Doc Archival</h2>
        <a href="index.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="myDocument.php" class="active"><i class="fas fa-folder"></i> My Documents</a>
        <a href="#"><i class="fas fa-cog"></i> Settings</a>
    </div>

    <!-- Top Navigation -->
    <div class="top-nav">
        <h2>My Documents</h2>
        <input type="text" placeholder="Search documents...">
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="upload-file" id="upload">
            <h3>Upload a Document</h3>
            <form id="uploadForm" action="upload_handler.php" method="post" enctype="multipart/form-data">
                <input type="file" name="document" id="document" required>
                <button type="button" id="uploadButton" class="hidden">Upload</button>
            </form>
        </div>

        <!-- Error Popup -->
        <div class="popup-error" id="fileErrorPopup" style="display: none;">
            <button class="exit-button" onclick="closePopup('fileErrorPopup')">X</button>
            <h3>Select A File First</h3>
        </div>

        <!-- Upload Popup -->
        <div id="uploadPopup" class="popup">
            <h3>Upload Your File</h3>
            <input type="file">
            <button id="closePopup">Close</button>
        </div>

        <!-- Document Types Section -->
        <div class="category-header">Document Types</div>
        <div class="category">
            <div class="category-item">
                <i class="fas fa-file-pdf"></i>
                <p>Reports</p>
            </div>
            <div class="category-item">
                <i class="fas fa-file-word"></i>
                <p>Proposals</p>
            </div>
            <div class="category-item">
                <i class="fas fa-file-excel"></i>
                <p>Data</p>
            </div>
        </div>

        <!-- Stored Files Section -->
        <h3>Stored Files</h3>
        <div class="file-section">
            <div class="file">
                <i class="fas fa-file-pdf"></i>
                <p>Report.pdf</p>
            </div>
            <div class="file">
                <i class="fas fa-file-word"></i>
                <p>Proposal.docx</p>
            </div>
            <div class="file">
                <i class="fas fa-file-excel"></i>
                <p>Data.xlsx</p>
            </div>
        </div>
    </div>

    <!-- Physical Copy Popup -->
    <div class="popup-questionnaire" id="physicalCopyPopup">
        <button class="exit-button" onclick="closePopup('physicalCopyPopup')">X</button>
        <h3>Does a Physical Copy Exist?</h3>
        <div class="button-group">
            <button id="yesPhysical" class="popup-button">Yes</button>
            <button id="noPhysical" class="popup-button">No</button>
        </div>
    </div>

    <!-- Physical Details Popup -->
    <div class="popup-questionnaire" id="physicalDetailsPopup" style="display: none;">
        <button class="exit-button" onclick="closePopup('physicalDetailsPopup')">X</button>
        <h3>Provide Physical Copy Details</h3>
        <form id="physicalDetailsForm">
            <label for="department">Department/College:</label>
            <input type="text" id="department" name="department" required>

            <label for="building">Building:</label>
            <input type="text" id="building" name="building" required>

            <label for="office">Office:</label>
            <input type="text" id="office" name="office" required>

            <label for="shelf">Shelf:</label>
            <input type="text" id="shelf" name="shelf" required>

            <button type="submit" class="submit-button">Submit</button>
        </form>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const uploadButton = document.getElementById("uploadButton");
            const physicalCopyPopup = document.getElementById("physicalCopyPopup");
            const fileErrorPopup = document.getElementById("fileErrorPopup");
            const yesPhysical = document.getElementById("yesPhysical");
            const noPhysical = document.getElementById("noPhysical");
            const exitButtons = document.querySelectorAll(".exit-button");

            // Show the first popup when "Upload" button is clicked
            uploadButton.addEventListener("click", function() {
                const fileInput = document.getElementById("document");

                if (fileInput.files.length === 0) {
                    fileErrorPopup.style.display = "block"; // Show error popup
                } else {
                    physicalCopyPopup.style.display = "block"; // Proceed to physical copy popup
                }
            });

            // Show the details popup when "Yes" is clicked
            yesPhysical.addEventListener("click", function() {
                physicalCopyPopup.style.display = "none";
                document.getElementById("physicalDetailsPopup").style.display = "block";
            });

            // Hide all popups when "No" is clicked
            noPhysical.addEventListener("click", function() {
                physicalCopyPopup.style.display = "none";
                document.getElementById("uploadForm").submit();
            });

            // Close any popup when the exit button is clicked
            exitButtons.forEach(button => {
                button.addEventListener("click", function() {
                    this.parentElement.style.display = "none";
                });
            });

            // Ensure file selection enables upload button
            document.getElementById("document").addEventListener("change", function() {
                uploadButton.style.display = "inline-block";
            });
        });

        function toggleRecentFiles() {
            const recentFiles = document.querySelector('.recent-files-section');
            recentFiles.classList.toggle('hidden');
        }

        function toggleRightSidebar() {
            document.getElementById('rightSidebar').classList.toggle('active');
        }
    </script>
</body>

</html>
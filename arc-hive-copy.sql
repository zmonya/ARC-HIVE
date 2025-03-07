-- Create the users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    department VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client') NOT NULL DEFAULT 'client',
    profile_pic VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create the departments table
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
);

-- Insert the departments
INSERT INTO departments (name) VALUES
    ('College of Agriculture and Forestry'),
    ('College of Arts and Sciences'),
    ('College of Business and Management'),
    ('College of Education'),
    ('College of Engineering and Technology'),
    ('College of Veterinary Medicine'),
    ('Admission and Registration Services'),
    ('Audit Offices'),
    ('External Linkages and International Affairs'),
    ('Management Information Systems'),
    ('Office of the President');

-- Create the files table
CREATE TABLE files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    recipients TEXT NOT NULL, -- Store multiple recipients as a comma-separated list
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user_id INT,
    file_size INT, -- Change to INT for easier size comparisons
    file_type VARCHAR(50),
    file_category VARCHAR(255),
    CONSTRAINT fk_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Create the activity_log table
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create the notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    icon VARCHAR(255),
    type VARCHAR(50),
    message VARCHAR(255),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
-- Create the recently_deleted table
CREATE TABLE recently_deleted (
    id INT AUTO_INCREMENT PRIMARY KEY,
    file_id INT, -- Reference to the files table
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (file_id) REFERENCES files(id) ON DELETE CASCADE
);
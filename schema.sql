CREATE DATABASE IF NOT EXISTS classroom_checkin;
USE classroom_checkin;

CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('student', 'instructor') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE classes (
    class_id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(100) NOT NULL,
    class_code VARCHAR(20) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE instructs (
    instructs_id INT PRIMARY KEY AUTO_INCREMENT,
    instructor_id INT NOT NULL,
    class_id INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    FOREIGN KEY (instructor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    UNIQUE KEY unique_instructor_class (instructor_id, class_id, semester, year)
);

CREATE TABLE enrolls (
    enrollment_id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    semester VARCHAR(20) NOT NULL,
    year INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, class_id, semester, year)
);

CREATE TABLE class_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    approved_location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_radius INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(class_id) ON DELETE CASCADE
);

CREATE TABLE attendance (
    attendance_id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_in_location VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('present', 'late', 'absent') DEFAULT 'present',
    FOREIGN KEY (session_id) REFERENCES class_sessions(session_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (session_id, student_id)
);

INSERT INTO users (first_name, last_name, email, password, user_type) VALUES
('John', 'Smith', 'johnsmith@kean.edu', 'password', 'instructor'),
('Jane', 'Doe', 'janedoe@kean.edu', 'password', 'student'),
('Bob', 'Johnson', 'bobjohnson@kean.edu', 'password', 'student'),
('Alice', 'Williams', 'alicewilliams@kean.edu', 'password', 'student');

INSERT INTO classes (class_name, class_code, description) VALUES
('Web Development', 'CS401', 'Introduction to Web Technologies'),
('Database Systems', 'CS301', 'Relational Database Design and SQL'),
('Software Engineering', 'CS402', 'Software Development Life Cycle');

INSERT INTO instructs (instructor_id, class_id, semester, year) VALUES
(1, 1, 'Fall', 2024),
(1, 2, 'Fall', 2024);

INSERT INTO enrolls (student_id, class_id, semester, year) VALUES
(2, 1, 'Fall', 2024),
(3, 1, 'Fall', 2024),
(4, 1, 'Fall', 2024),
(2, 2, 'Fall', 2024);

INSERT INTO class_sessions (class_id, session_date, session_time, approved_location, latitude, longitude) VALUES
(1, CURDATE(), '10:00:00', 'Building A, Room 101', 40.7128, -74.0060),
(1, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '10:00:00', 'Building A, Room 101', 40.7128, -74.0060),
(2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 'Building B, Room 205', 40.7138, -74.0070);
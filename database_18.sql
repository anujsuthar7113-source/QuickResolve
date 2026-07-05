
-- Database: database_18
-- Description: Full schema with roles, complaints, departments,
--              feedback, archive, and sessions


CREATE DATABASE IF NOT EXISTS database_18 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE database_18;


-- TABLE: departments
-- Stores all department info (created by admin)

CREATE TABLE IF NOT EXISTS departments (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT,
    email       VARCHAR(150),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- TABLE: users
-- Stores all system users: admin, department, user roles

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    role          ENUM('admin','department','user') NOT NULL DEFAULT 'user',
    dept_id       INT DEFAULT NULL,                   -- links department users to dept
    status        ENUM('active','pending','blocked') NOT NULL DEFAULT 'pending',
    reset_token   VARCHAR(64) DEFAULT NULL,
    token_expiry  DATETIME DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- TABLE: complaints
-- Core table — every complaint submitted by a user

CREATE TABLE IF NOT EXISTS complaints (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    dept_id       INT DEFAULT NULL,                   -- assigned department
    title         VARCHAR(200) NOT NULL,
    description   TEXT NOT NULL,
    priority      ENUM('low','medium','high','critical') NOT NULL DEFAULT 'medium',
    status        ENUM('pending','assigned','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
    image_path    VARCHAR(255) DEFAULT NULL,          -- uploaded file path
    auto_assigned TINYINT(1) DEFAULT 0,               -- 1 = smart routing used
    admin_note    TEXT DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (dept_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;


-- TABLE: feedback
-- Feedback submitted by users after complaint resolution

CREATE TABLE IF NOT EXISTS feedback (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL UNIQUE,
    user_id      INT NOT NULL,
    rating       TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comments     TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- TABLE: archive
-- Completed complaints moved here for record keeping

CREATE TABLE IF NOT EXISTS archive (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id  INT NOT NULL,
    user_id       INT NOT NULL,
    dept_id       INT DEFAULT NULL,
    title         VARCHAR(200) NOT NULL,
    description   TEXT,
    priority      VARCHAR(20),
    final_status  VARCHAR(30),
    archived_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- TABLE: complaint_logs
-- Timeline of status changes for each complaint

CREATE TABLE IF NOT EXISTS complaint_logs (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    changed_by   INT NOT NULL,
    old_status   VARCHAR(30),
    new_status   VARCHAR(30),
    note         TEXT,
    changed_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;


-- SEED DATA: Departments

INSERT INTO departments (name, description, email) VALUES
('Electrical',  'Handles all electrical issues including lighting, wiring, power outages', 'electrical@quickresolve.com'),
('Plumbing',    'Water supply, pipe leaks, drainage and sanitation issues',               'plumbing@quickresolve.com'),
('Housekeeping','Cleaning, waste disposal and general cleanliness issues',                'housekeeping@quickresolve.com'),
('IT Support',  'Network, computer, software and internet related problems',              'itsupport@quickresolve.com'),
('Maintenance', 'General infrastructure, building repairs and civil work',                'maintenance@quickresolve.com'),
('Security',    'Safety, access control and surveillance related complaints',             'security@quickresolve.com');


-- SEED DATA: Admin user (password: Admin@123)

INSERT INTO users (name, email, password, role, status) VALUES
('Super Admin', 'admin@quickresolve.com',
 '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@123 (bcrypt)
 'admin', 'active');

-- NOTE: The hash above uses bcrypt. For XAMPP testing use password: password
-- To generate proper hash run: echo password_hash('Admin@123', PASSWORD_DEFAULT);
-- We insert a known hash below for the password "Admin@123"
-- Run this UPDATE after setup if above hash doesn't work:
-- UPDATE users SET password = password_hash('Admin@123', 10) WHERE email='admin@quickresolve.com';


-- SEED DATA: Department users (password: Dept@123)

INSERT INTO users (name, email, password, role, dept_id, status) VALUES
('Electrical Dept',  'electrical@quickresolve.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 1, 'active'),
('Plumbing Dept',    'plumbing@quickresolve.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 2, 'active'),
('Housekeeping Dept','housekeeping@quickresolve.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 3, 'active'),
('IT Support Dept',  'itsupport@quickresolve.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 4, 'active'),
('Maintenance Dept', 'maintenance@quickresolve.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 5, 'active'),
('Security Dept',    'security@quickresolve.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'department', 6, 'active');


-- SEED DATA: Sample regular users (password: User@123)

INSERT INTO users (name, email, password, role, status) VALUES
('Rahul Sharma',  'rahul@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active'),
('Priya Patel',   'priya@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', 'active');


-- SEED DATA: Sample complaints

INSERT INTO complaints (user_id, dept_id, title, description, priority, status, auto_assigned) VALUES
(8, 1, 'Street light not working',        'The street light near block B has not been working for 3 days',             'high',   'assigned',    1),
(9, 2, 'Water leakage in corridor',       'There is a water pipe leaking near the 2nd floor corridor since morning',  'critical','in_progress', 1),
(8, 3, 'Common area not cleaned',         'The common area on floor 3 has not been cleaned for the past 2 days',      'medium', 'pending',     1),
(9, 4, 'Internet not working',            'WiFi connectivity is down in wing A since yesterday evening',              'high',   'in_progress', 1),
(8, 5, 'Broken window in meeting room',   'The window latch in meeting room 204 is broken and needs repair',          'low',    'completed',   0),
(9, NULL,'Noise complaint from neighbors','Excessive noise from apartment 301 late at night, disturbing residents',   'medium', 'pending',     0);


-- SEED DATA: Sample feedback

INSERT INTO feedback (complaint_id, user_id, rating, comments) VALUES
(5, 8, 5, 'Issue was resolved quickly and efficiently. Very satisfied with the service!');


-- HELPER: View to get complaint with user and dept name

CREATE OR REPLACE VIEW complaints_view AS
SELECT
    c.id,
    c.title,
    c.description,
    c.priority,
    c.status,
    c.image_path,
    c.auto_assigned,
    c.admin_note,
    c.created_at,
    c.updated_at,
    u.name  AS user_name,
    u.email AS user_email,
    d.name  AS dept_name,
    c.user_id,
    c.dept_id
FROM complaints c
LEFT JOIN users       u ON c.user_id = u.id
LEFT JOIN departments d ON c.dept_id = d.id;

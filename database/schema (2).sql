-- ============================================================
-- ERP Event Management System — Day 1 Schema
-- Fresh build, Option B (Flutter + PHP REST API + MySQL)
-- ============================================================

DROP DATABASE IF EXISTS erp_event_system;
CREATE DATABASE erp_event_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE erp_event_system;

-- ------------------------------------------------------------
-- 1. roles
-- ------------------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
    -- Student, Faculty, Coordinator, HOD, Principal, Director, VC, Admin
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 2. departments
-- ------------------------------------------------------------
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 3. users
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    department_id INT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 4. event_categories
-- ------------------------------------------------------------
CREATE TABLE event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    level ENUM('department', 'university') NOT NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 5. event_requests
--    Single table with current_status + current_approver_role_id
--    (per locked architectural decision — no generic workflow engine)
-- ------------------------------------------------------------
CREATE TABLE event_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    category_id INT NOT NULL,
    level ENUM('department', 'university') NOT NULL,
    department_id INT NOT NULL,
    organizer_id INT NOT NULL,               -- student/faculty who requested
    faculty_coordinator_id INT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue VARCHAR(200) NOT NULL,
    participants_count INT NOT NULL DEFAULT 0,
    budget DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    description TEXT NULL,
    current_status VARCHAR(50) NOT NULL DEFAULT 'Draft',
    current_approver_role_id INT NULL,
    approval_id VARCHAR(50) NULL UNIQUE,     -- set on final approval
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES event_categories(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (organizer_id) REFERENCES users(id),
    FOREIGN KEY (faculty_coordinator_id) REFERENCES users(id),
    FOREIGN KEY (current_approver_role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 6. approval_history
--    Appended on every action (approve/reject/send-back)
-- ------------------------------------------------------------
CREATE TABLE approval_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_request_id INT NOT NULL,
    role_id INT NOT NULL,
    user_id INT NOT NULL,
    action ENUM('submitted', 'approved', 'rejected', 'sent_back') NOT NULL,
    remarks TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_request_id) REFERENCES event_requests(id),
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 7. attachments
-- ------------------------------------------------------------
CREATE TABLE attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_request_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_request_id) REFERENCES event_requests(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 8. notifications
--    Polling-based, read by Flutter client
-- ------------------------------------------------------------
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_request_id INT NULL,
    message VARCHAR(500) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (event_request_id) REFERENCES event_requests(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 9. audit_logs
-- ------------------------------------------------------------
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NULL,
    details TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- 10. auth_tokens
--     Token-based auth (no PHP sessions)
-- ------------------------------------------------------------
CREATE TABLE auth_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Helpful indexes
-- ------------------------------------------------------------
CREATE INDEX idx_event_requests_status ON event_requests(current_status);
CREATE INDEX idx_event_requests_department ON event_requests(department_id);
CREATE INDEX idx_approval_history_request ON approval_history(event_request_id);
CREATE INDEX idx_notifications_user ON notifications(user_id, is_read);
CREATE INDEX idx_auth_tokens_token ON auth_tokens(token);

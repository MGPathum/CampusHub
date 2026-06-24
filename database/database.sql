-- =============================================================
--  CampusHub - Database Schema
--  Run this script once to set up the database.
-- =============================================================

CREATE DATABASE IF NOT EXISTS campushub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE campushub;

-- -------------------------------------------------------------
-- Table: users
-- Stores both students and admins (differentiated by 'role').
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    full_name   VARCHAR(100)    NOT NULL,
    email       VARCHAR(150)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,               -- bcrypt hash
    role        ENUM('student','admin') NOT NULL DEFAULT 'student',
    student_id  VARCHAR(30)     DEFAULT NULL,           -- campus student ID (students only)
    course      VARCHAR(100)    DEFAULT NULL,
    year_level  TINYINT UNSIGNED DEFAULT NULL,          -- e.g. 1–4
    bio         TEXT            DEFAULT NULL,
    profile_photo VARCHAR(255)  DEFAULT NULL,           -- path relative to uploads/profiles/
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_role (role),
    INDEX idx_student_id (student_id)
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Table: events
-- Stores campus events/activities managed by admins.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS events (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    title           VARCHAR(200)    NOT NULL,
    description     TEXT            DEFAULT NULL,
    location        VARCHAR(200)    DEFAULT NULL,
    event_date      DATE            NOT NULL,
    event_time      TIME            DEFAULT NULL,
    max_slots       SMALLINT UNSIGNED DEFAULT NULL,     -- NULL = unlimited
    status          ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    banner_image    VARCHAR(255)    DEFAULT NULL,       -- path relative to uploads/media/
    teaser_video    VARCHAR(255)    DEFAULT NULL,       -- path relative to uploads/media/ or external URL
    created_by      INT UNSIGNED    NOT NULL,           -- FK → users.id (admin)
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_event_date (event_date),
    INDEX idx_status (status),
    FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Table: registrations
-- Maps students to events (many-to-many).
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS registrations (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    student_id      INT UNSIGNED    NOT NULL,           -- FK → users.id
    event_id        INT UNSIGNED    NOT NULL,           -- FK → events.id
    status          ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    attendance      VARCHAR(20)     NOT NULL DEFAULT 'not_marked', -- 'not_marked','attended','absent'
    registered_at   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes           TEXT            DEFAULT NULL,       -- student notes or admin remarks
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_event (student_id, event_id), -- prevent duplicate registration
    FOREIGN KEY (student_id) REFERENCES users(id)   ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (event_id)   REFERENCES events(id)  ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;


-- -------------------------------------------------------------
-- Table: media
-- Tracks all files uploaded by students or admins.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS media (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    uploader_id     INT UNSIGNED    NOT NULL,           -- FK → users.id
    event_id        INT UNSIGNED    DEFAULT NULL,       -- FK → events.id (optional association)
    file_name       VARCHAR(255)    NOT NULL,           -- original filename
    file_path       VARCHAR(255)    NOT NULL,           -- stored path relative to uploads/
    file_type       VARCHAR(50)     NOT NULL,           -- MIME type (image/jpeg, video/mp4, etc.)
    file_size       INT UNSIGNED    NOT NULL,           -- bytes
    caption         VARCHAR(255)    DEFAULT NULL,
    uploaded_at     TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (uploader_id) REFERENCES users(id)  ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (event_id)    REFERENCES events(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;


-- =============================================================
--  Seed Data
-- =============================================================

-- Default admin account  (password: Admin@123)
-- Hash generated with: password_hash('Admin@123', PASSWORD_BCRYPT, ['cost'=>10])
INSERT INTO users (full_name, email, password, role) VALUES
('Admin User', 'admin@campushub.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample student account  (password: Student@123)
-- Hash generated with: password_hash('Student@123', PASSWORD_BCRYPT, ['cost'=>10])
INSERT INTO users (full_name, email, password, role, student_id, course, year_level) VALUES
('Juan dela Cruz', 'juan@student.edu', '$2y$10$TKh8H1.PfQ0A0bEz5muoMuUo.omfg5BxVB6J.sHzU/2YuoOzC2Gka', 'student', '2024-00001', 'BS Computer Science', 2);

-- Sample events
INSERT INTO events (title, description, location, event_date, event_time, max_slots, status, created_by) VALUES
('Freshmen Orientation 2024', 'Welcome event for all new students. Get to know the campus, meet faculty, and join clubs.', 'Main Auditorium', '2024-08-15', '09:00:00', 300, 'upcoming', 1),
('Web Development Workshop', 'Hands-on workshop covering HTML, CSS, PHP and MySQL fundamentals.', 'Computer Lab 3', '2024-09-10', '13:00:00', 40, 'upcoming', 1),
('Campus Cultural Night', 'Annual cultural celebration featuring performances from all departments.', 'Open Grounds', '2024-10-20', '18:00:00', NULL, 'upcoming', 1);

CREATE DATABASE IF NOT EXISTS campushub CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE campushub;

CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    full_name     VARCHAR(100)     NOT NULL,
    email         VARCHAR(150)     NOT NULL UNIQUE,
    password      VARCHAR(255)     NOT NULL,
    role          ENUM('student','admin') NOT NULL DEFAULT 'student',
    student_id    VARCHAR(30)      DEFAULT NULL,
    course        VARCHAR(100)     DEFAULT NULL,
    year_level    TINYINT UNSIGNED DEFAULT NULL,
    bio           TEXT             DEFAULT NULL,
    profile_photo VARCHAR(255)     DEFAULT NULL,
    created_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
    id           INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    title        VARCHAR(200)      NOT NULL,
    description  TEXT              DEFAULT NULL,
    location     VARCHAR(200)      DEFAULT NULL,
    event_date   DATE              NOT NULL,
    event_time   TIME              DEFAULT NULL,
    max_slots    SMALLINT UNSIGNED DEFAULT NULL,
    status       ENUM('upcoming','ongoing','completed','cancelled') NOT NULL DEFAULT 'upcoming',
    banner_image VARCHAR(255)      DEFAULT NULL,
    teaser_video VARCHAR(255)      DEFAULT NULL,
    created_by   INT UNSIGNED      NOT NULL,
    created_at   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registrations (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id    INT UNSIGNED NOT NULL,
    event_id      INT UNSIGNED NOT NULL,
    status        ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    attendance    VARCHAR(20)  NOT NULL DEFAULT 'not_marked',
    registered_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notes         TEXT         DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_student_event (student_id, event_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (event_id)   REFERENCES events(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS media (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    uploader_id INT UNSIGNED NOT NULL,
    event_id    INT UNSIGNED DEFAULT NULL,
    file_name   VARCHAR(255) NOT NULL,
    file_path   VARCHAR(255) NOT NULL,
    file_type   VARCHAR(50)  NOT NULL,
    file_size   INT UNSIGNED NOT NULL,
    caption     VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (uploader_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (event_id)    REFERENCES events(id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (full_name, email, password, role) VALUES
('Admin User', 'admin@campushub.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

INSERT INTO users (full_name, email, password, role, student_id, course, year_level) VALUES
('Juan dela Cruz', 'juan@student.edu', '$2y$10$TKh8H1.PfQ0A0bEz5muoMuUo.omfg5BxVB6J.sHzU/2YuoOzC2Gka', 'student', '2024-00001', 'BS Computer Science', 2);

INSERT INTO events (title, description, location, event_date, event_time, max_slots, status, created_by) VALUES
('Web Development Bootcamp 2026', 'A two-day intensive bootcamp covering modern web technologies including HTML5, CSS3, PHP, and MySQL. Open to all IT students.', 'Computer Laboratory 3', '2026-08-15', '09:00:00', 40, 'upcoming', 1),
('Cybersecurity Awareness Seminar', 'A half-day seminar on ethical hacking fundamentals, network security, and safe online practices for students and faculty.', 'Main Auditorium', '2026-09-05', '10:00:00', 150, 'upcoming', 1),
('Campus Tech Fair 2026', 'Annual technology fair where students showcase their final year projects and capstone systems. Judges from the industry will attend.', 'Open Grounds', '2026-10-22', '08:00:00', NULL, 'upcoming', 1);

# CampusHub

A campus event management web application built for the Web Programming I (H7DT) assignment at the Java Institute for Advanced Technology.

Students can browse and register for campus events, upload media, and track their attendance status. Administrators can manage events, approve registrations, and publish announcements.

---

## Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | PHP (Procedural, MySQLi)          |
| Database   | MySQL 5.7+ / MariaDB              |
| Frontend   | HTML5, CSS3, Vanilla JavaScript   |
| Server     | Apache (XAMPP)                    |
| Data       | XML (announcements via SimpleXML) |

---

## Features

- Student registration and login with bcrypt password hashing
- Event browsing, registration, and cancellation
- Attendance tracking (Attended / Absent) managed by admin
- Re-registration support for cancelled or absent students
- Profile management with photo upload
- Media uploads (images, video, audio) linked to events
- XML-driven announcements displayed on homepage
- Admin panel: manage events, registrations, students, media
- PRG (Post/Redirect/Get) pattern on all form submissions
- Prepared statements (MySQLi) throughout — no raw queries

---

## Project Structure

```
campushub/
├── admin/          Admin panel pages
├── assets/         CSS and JavaScript
├── auth/           Login and registration
├── config/         Database connection
├── data/           announcements.xml
├── database/       Schema SQL file
├── includes/       Shared header, footer, functions
├── student/        Student panel pages
├── uploads/        User-uploaded files
├── campus_hub.sql  Clean database export
├── index.php       Homepage
└── announcements.php
```

---

## Local Setup

### Requirements
- XAMPP (Apache + MySQL + PHP 7.4 or higher)
- A web browser

### Steps

1. Clone or copy the project folder into your XAMPP `htdocs` directory:
   ```
   C:\xampp\htdocs\campushub\
   ```

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Open **phpMyAdmin** at `http://localhost/phpmyadmin`.

4. Create a new database named `campushub`.

5. Import the schema by selecting the `campushub` database, going to the **Import** tab, and uploading `campus_hub.sql`.

6. Open `config/config.php` and update the database credentials if needed:
   ```php
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   ```

7. Visit `http://localhost/campushub/` in your browser.

8. Run the password setup script once to generate valid bcrypt hashes:
   ```
   http://localhost/campushub/setup_passwords.php
   ```
   Delete or disable this file after running it.

---

## Default Login Credentials

| Role    | Email                  | Password     |
|---------|------------------------|--------------|
| Admin   | admin@campushub.edu    | Admin@123    |
| Student | juan@student.edu       | Student@123  |

> Passwords only work after running `setup_passwords.php`.

---

## Assignment

**Unit:** Web Programming I — H7DT  
**Institution:** Java Institute for Advanced Technology  
**Year:** 2026

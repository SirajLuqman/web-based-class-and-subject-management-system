# Web-Based Class and Subject Management System

## 🌐 Project Overview

The **Web-Based Class and Subject Management System** is a comprehensive full-stack web application designed to revolutionize academic scheduling and course management in higher education institutions. Developed to address the inefficiencies of manual timetable systems, this platform provides a centralized solution for students to register for courses and for administrators to manage academic schedules, conflict resolution, and institutional communications.

The application eliminates scheduling conflicts through automated detection, streamlines administrative workflows, and ensures real-time communication between students and faculty—transforming complex academic administration into an efficient, user-friendly digital experience.

---

## ✨ Key Features

### 👨‍🎓 **Student Features**
*   **Personalized Dashboard:** View registered subjects, available courses, and notifications at a glance.
*   **Intelligent Course Registration:** Browse and register for available subjects with automatic **real-time conflict detection** to prevent timetable clashes.
*   **Interactive Timetable:** View a clean, organized weekly schedule displaying all registered subjects with times and details.
*   **Notification Center:** Receive and manage institutional announcements. Unread notifications are visually highlighted.
*   **Profile Management:** Update personal information and securely change passwords.

### 👨‍🏫 **Administrator Features**
*   **Comprehensive Admin Dashboard:** Overview of all student registrations, pending conflicts, and system statistics.
*   **Full Subject Management:** Create, edit, and delete subjects. Define course codes, assign faculty, and set study levels.
*   **Schedule Management:** Create specific "offered subject" instances with days, times, and dates for the semester.
*   **Conflict Resolution Hub:** Identify and resolve scheduling conflicts before they affect students.
*   **Broadcast System:** Create, edit, and delete announcements that are instantly delivered to all students.
*   **User Management:** Manage both student and administrator accounts.

### 🔒 **System-Wide Features**
*   **Role-Based Access Control (RBAC):** Secure separation between student and admin functionalities.
*   **Secure Authentication:** Login system with session management and "Forgot Password" functionality.
*   **Responsive Design:** Fully functional on desktops, tablets, and mobile devices.
*   **Professional UI:** Clean, intuitive interface with consistent navigation and visual feedback.

---

## 🛠️ Tech Stack

### **Frontend**
*   **Languages:** HTML5, CSS3, JavaScript (Vanilla)
*   **Styling:** Custom CSS for responsive design and layout

### **Backend**
*   **Server-Side Language:** PHP
*   **Architecture:** Traditional server-side rendering with modular structure

### **Database**
*   **Database System:** MariaDB (via XAMPP)
*   **Query Language:** SQL
*   **Key Design Decision:** Database relationships and integrity checks are managed in the **application layer (PHP)** rather than through database-enforced foreign keys, providing greater flexibility and control over business logic.

### **Development & Deployment**
*   **Local Server Stack:** XAMPP (Apache 2.4, PHP 8.2.12, MariaDB 10.4.32)
*   **Development IDE:** Visual Studio Code
*   **Browser Testing:** Google Chrome

### **Security Implementation**
*   **Session Management:** Secure user sessions with role validation on every page
*   **Input Validation & Sanitization:** Protection against SQL Injection and XSS attacks
*   **CSRF Protection:** Security tokens in all forms and AJAX requests
*   **Password Security:** Hashing algorithms for secure password storage

---

## 🗄️ Database Architecture

The system uses a relational database with the following core tables:

*   **`users`:** Stores all user accounts (students & admins) with role differentiation
*   **`subjects_master`:** Master list of all university courses
*   **`offered_subjects`:** Specific instances of subjects offered in a given semester with schedule details
*   **`registrations`:** Links students to their registered subjects
*   **`faculties` & `study_levels`:** Reference tables for institutional structure
*   **`notifications` & `notifications_read`:** Manages announcements and read status

**Design Philosophy:** Relationships between tables are maintained through application logic in PHP rather than database foreign key constraints. This approach was chosen for:
*   Greater flexibility in complex queries and conditional joins
*   Simplified development and testing during prototyping
*   Explicit control over data integrity and cascading operations
*   Enhanced modularity for future feature expansion

---

## 🚀 Installation & Setup Guide

### **Prerequisites**
*   XAMPP (or similar LAMP/WAMP stack)
*   Modern web browser (Chrome recommended)
*   Text editor/IDE (VS Code, Sublime Text, etc.)

### **Step-by-Step Setup**

1.  **Install XAMPP**
    *   Download and install XAMPP from [Apache Friends](https://www.apachefriends.org/)
    *   Start Apache and MySQL services from the XAMPP Control Panel

2.  **Set Up the Project**
    ```bash
    # Clone or copy the project files to your XAMPP htdocs directory
    # Typically: C:\xampp\htdocs\ (Windows) or /Applications/XAMPP/htdocs/ (macOS)
    ```

3.  **Create the Database**
    *   Open phpMyAdmin (http://localhost/phpmyadmin)
    *   Create a new database named `university_portal`
    *   Import the provided SQL file (if available) or execute the schema creation script

4.  **Configure Database Connection**
    *   Locate the configuration file (e.g., `config.php` or `db_connection.php`)
    *   Update database credentials to match your local setup:
    ```php
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');  // Default XAMPP username
    define('DB_PASS', '');      // Default XAMPP password (empty)
    define('DB_NAME', 'university_portal');
    ```

5.  **Access the Application**
    *   Open your browser and navigate to: `http://localhost/[your-project-folder]/`
    *   Use the following default credentials for testing:
      *   **Admin:** Username: `admin`, Password: `admin`
      *   **Student:** Username: `student`, Password: `student`

---

### 🧪 Testing & Quality Assurance

All core modules of the system were put through rigorous testing to ensure reliability, security, and a seamless user experience. The results are summarized below:

*   **Authentication & Session Management**
    *   **Test Focus:** Role-based login (Student/Admin), session creation/validation, and password verification.
    *   **Result:** ✅ **Passed**
    *   **Notes:** System successfully authenticates users and redirects them to the correct role-specific dashboard (Student or Admin). Unauthorized access attempts are blocked.

*   **Student Dashboard**
    *   **Test Focus:** Accurate display of registered/offered subject counts and real-time notification delivery.
    *   **Result:** ✅ **Passed**
    *   **Notes:** All widgets and counters update correctly based on database state. Notifications appear instantly for students.

*   **Class Scheduling & Timetable**
    *   **Test Focus:** Timetable generation, automatic clash detection during registration, and add/drop functionality.
    *   **Result:** ✅ **Passed**
    *   **Notes:** The timetable renders correctly. The system's core logic successfully prevents students from registering for subjects with overlapping times.

*   **Subject Management (Admin)**
    *   **Test Focus:** Full CRUD (Create, Read, Update, Delete) operations for subjects and offered subjects, including validation for overlapping time slots.
    *   **Result:** ✅ **Passed**
    *   **Notes:** Admins can manage all subject data without issues. Validation effectively prevents the creation of duplicate or schedule-conflicting subjects.

*   **Notifications System**
    *   **Test Focus:** Admin creation/deletion of announcements and the student-side process of viewing/marking notifications as read.
    *   **Result:** ✅ **Passed**
    *   **Notes:** Notifications are delivered to students in real-time. The read/unread status tracking works correctly.

*   **Security**
    *   **Test Focus:** CSRF protection, input sanitization to prevent SQL injection/XSS, and robust session handling.
    *   **Result:** ✅ **Passed**
    *   **Notes:** All forms include security tokens. Inputs are properly validated. No vulnerabilities leading to unauthorized access were found.

*   **Responsive Design**
    *   **Test Focus:** Layout and functionality across various devices and screen sizes (desktop, tablet, mobile).
    *   **Result:** ✅ **Passed**
    *   **Notes:** The interface adapts correctly, and all interactive elements remain usable on different devices.

---

## 📈 Impact & Benefits

*   **For Students:** Reduced confusion, guaranteed conflict-free schedules, and instant access to academic updates.
*   **For Administrators:** Automated conflict resolution, streamlined communication, and significant reduction in manual administrative tasks.
*   **For Institutions:** Enhanced operational efficiency, improved data accuracy, and a foundation for scalable digital transformation.

**Future Roadmap:** The modular architecture supports expansion into payment systems, faculty management, exam scheduling, and advanced analytics modules.

---

## 📄 License & Acknowledgments

This project was developed as an academic assignment for the **Web Programming (BIT4023)** course at City University Malaysia. The system demonstrates practical implementation of full-stack web development principles, database design, and security best practices.

**Supervising Lecturer:** Rishma Fathima Binti Basher

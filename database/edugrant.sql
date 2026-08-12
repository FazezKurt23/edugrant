-- =============================================================
-- EduGrant Database
-- A Web-Based Scholarship Information and Application
-- Management System for Students (BSIT Capstone Demo)
--
-- Import this file through phpMyAdmin (or mysql CLI) to create
-- the database, tables, and demo data.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------------
-- Database creation
-- -------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `edugrant`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `edugrant`;

-- =============================================================
-- users
-- =============================================================
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(190) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student','provider','admin') NOT NULL DEFAULT 'student',
  `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- student_profiles
-- =============================================================
DROP TABLE IF EXISTS `student_profiles`;
CREATE TABLE `student_profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `birth_date` DATE DEFAULT NULL,
  `gender` ENUM('male','female','other') DEFAULT NULL,
  `school` VARCHAR(190) DEFAULT NULL,
  `course` VARCHAR(190) DEFAULT NULL,
  `year_level` VARCHAR(50) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `province` VARCHAR(100) DEFAULT NULL,
  `contact_number` VARCHAR(30) DEFAULT NULL,
  `gpa` DECIMAL(4,2) DEFAULT NULL,
  `family_income` DECIMAL(12,2) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_profile_user` (`user_id`),
  UNIQUE KEY `uq_student_profile_student_id` (`student_id`),
  CONSTRAINT `fk_student_profiles_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- provider_profiles
-- =============================================================
DROP TABLE IF EXISTS `provider_profiles`;
CREATE TABLE `provider_profiles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `organization_name` VARCHAR(190) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `contact_person` VARCHAR(150) DEFAULT NULL,
  `contact_number` VARCHAR(30) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `website` VARCHAR(255) DEFAULT NULL,
  `verification_status` ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_profile_user` (`user_id`),
  CONSTRAINT `fk_provider_profiles_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- scholarships
-- =============================================================
DROP TABLE IF EXISTS `scholarships`;
CREATE TABLE `scholarships` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `scholarship_type` VARCHAR(100) DEFAULT NULL,
  `education_level` VARCHAR(100) DEFAULT NULL,
  `location` VARCHAR(190) DEFAULT NULL,
  `amount` VARCHAR(100) DEFAULT NULL,
  `benefits` TEXT DEFAULT NULL,
  `eligibility_summary` TEXT DEFAULT NULL,
  `deadline` DATE DEFAULT NULL,
  `official_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('draft','pending','approved','rejected','expired') NOT NULL DEFAULT 'draft',
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scholarships_provider` (`provider_id`),
  KEY `idx_scholarships_status` (`status`),
  KEY `idx_scholarships_deadline` (`deadline`),
  CONSTRAINT `fk_scholarships_provider` FOREIGN KEY (`provider_id`)
    REFERENCES `provider_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- scholarship_requirements
-- =============================================================
DROP TABLE IF EXISTS `scholarship_requirements`;
CREATE TABLE `scholarship_requirements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scholarship_id` INT UNSIGNED NOT NULL,
  `requirement` TEXT NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_req_scholarship` (`scholarship_id`),
  CONSTRAINT `fk_req_scholarship` FOREIGN KEY (`scholarship_id`)
    REFERENCES `scholarships` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- required_documents
-- =============================================================
DROP TABLE IF EXISTS `required_documents`;
CREATE TABLE `required_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `scholarship_id` INT UNSIGNED NOT NULL,
  `document_name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_docs_scholarship` (`scholarship_id`),
  CONSTRAINT `fk_docs_scholarship` FOREIGN KEY (`scholarship_id`)
    REFERENCES `scholarships` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- saved_scholarships
-- =============================================================
DROP TABLE IF EXISTS `saved_scholarships`;
CREATE TABLE `saved_scholarships` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `scholarship_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_saved_student_scholarship` (`student_id`,`scholarship_id`),
  KEY `idx_saved_scholarship` (`scholarship_id`),
  CONSTRAINT `fk_saved_student` FOREIGN KEY (`student_id`)
    REFERENCES `student_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_saved_scholarship` FOREIGN KEY (`scholarship_id`)
    REFERENCES `scholarships` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- applications (application tracking - not submission)
-- =============================================================
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `scholarship_id` INT UNSIGNED NOT NULL,
  `status` ENUM('interested','preparing','applied','under_review','approved','rejected') NOT NULL DEFAULT 'interested',
  `application_date` DATE NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_app_student_scholarship` (`student_id`,`scholarship_id`),
  KEY `idx_app_scholarship` (`scholarship_id`),
  KEY `idx_app_status` (`status`),
  CONSTRAINT `fk_app_student` FOREIGN KEY (`student_id`)
    REFERENCES `student_profiles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_app_scholarship` FOREIGN KEY (`scholarship_id`)
    REFERENCES `scholarships` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- notifications
-- =============================================================
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `type` ENUM('deadline','application','scholarship','system','approval','rejection') NOT NULL DEFAULT 'system',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_read` (`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- activity_logs
-- =============================================================
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(190) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_log_user` (`user_id`),
  CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- password_resets (development-mode password reset)
-- =============================================================
DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(255) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reset_token` (`token_hash`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================
-- DEMO USERS
-- =============================================================

-- Admin (Admin123!)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(1, 'EduGrant Administrator', 'admin@edugrant.local',
 '$2y$10$8Iwx9MXrqZMA0QzxMg9GYeeTcS8fUXT/xlsHha9oNsiZagOSlYa/u',
 'admin', 'active');

-- Students (password: Student123!)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(2, 'Juan Dela Cruz', 'student@edugrant.local',
 '$2y$10$0aX6E8Axtti/i3sWB3ElC.SJmnd52n.t8bt06NMNVSM.fNvKfKqgG',
 'student', 'active'),
(3, 'Maria Santos', 'student2@edugrant.local',
 '$2y$10$dZBpZru49VJFjAJq79ysIerOYvJY.yr3pCyEtVk3Ig.JnPLP3lcPe',
 'student', 'active'),
(4, 'Pedro Reyes', 'student3@edugrant.local',
 '$2y$10$vdpp2JZZSVbIudEf7hH2XOPSD/HlDvNQ5b/meW/mLvOnZ7tCch6be',
 'student', 'active');

-- Providers (password: Provider123!)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`) VALUES
(5, 'BrightFuture Foundation (Demo Provider)', 'provider@edugrant.local',
 '$2y$10$7sNsB5OHiO2wyJ1f3vPsJ.Wexxm.eLCkYQQCrvgSsKehg/.alkao.',
 'provider', 'active'),
(6, 'TechScholar Foundation (Demo Provider)', 'provider2@edugrant.local',
 '$2y$10$/.ltfkKfECfyDlVccuc3JOzZp0MO3A1fE4qq.zgTMLpA.EAZa6qgq',
 'provider', 'active');

-- =============================================================
-- DEMO STUDENT PROFILES
-- =============================================================
INSERT INTO `student_profiles`
(`id`, `user_id`, `student_id`, `first_name`, `middle_name`, `last_name`,
 `birth_date`, `gender`, `school`, `course`, `year_level`, `academic_level`,
 `address`, `city`, `province`, `contact_number`, `gpa`, `family_income`) VALUES
(1, 2, '2024-0001', 'Juan', 'Santos', 'Dela Cruz',
 '2005-03-14', 'male', 'Metro University', 'BS Information Technology', '2nd Year', 'College',
 '123 Rizal St.', 'Quezon City', 'Metro Manila', '09171234567', 1.75, 180000.00),
(2, 3, '2024-0002', 'Maria', 'Garcia', 'Santos',
 '2004-11-02', 'female', 'Metro University', 'BS Computer Science', '3rd Year', 'College',
 '456 Mabini St.', 'Manila', 'Metro Manila', '09189876543', 1.50, 150000.00),
(3, 4, '2024-0003', 'Pedro', 'Lopez', 'Reyes',
 '2005-06-21', 'male', 'Provincial State College', 'BS Information Technology', '1st Year', 'College',
 '789 Luna St.', 'Cebu City', 'Cebu', '09222333444', 1.90, 210000.00);

-- =============================================================
-- DEMO PROVIDER PROFILES
-- =============================================================
INSERT INTO `provider_profiles`
(`id`, `user_id`, `organization_name`, `description`, `contact_person`,
 `contact_number`, `address`, `website`, `verification_status`) VALUES
(1, 5, 'BrightFuture Foundation',
 'A DEMO scholarship foundation that supports college students in STEM and leadership programs.',
 'Ana Cruz', '09171112222', '10 EDSA Ave., Quezon City', 'https://brightfuture-demo.example.com', 'verified'),
(2, 6, 'TechScholar Foundation',
 'A DEMO technology-focused scholarship organization for IT and Computer Science students.',
 'Ben Dela Vega', '09173334444', '25 Ayala Blvd., Makati City', 'https://techscholar-demo.example.com', 'pending');

-- =============================================================
-- DEMO SCHOLARSHIPS
-- =============================================================
INSERT INTO `scholarships`
(`id`, `provider_id`, `title`, `description`, `scholarship_type`,
 `education_level`, `location`, `amount`, `benefits`, `eligibility_summary`,
 `deadline`, `official_url`, `status`, `rejection_reason`) VALUES
(1, 1, 'BrightFuture STEM Excellence Scholarship',
 'A DEMO scholarship for undergraduate students pursuing STEM-related degree programs with excellent academic standing.',
 'Merit-Based', 'Undergraduate', 'Metro Manila', 'PHP 50,000 per year',
 'Annual stipend of PHP 50,000, free academic mentorship, and priority access to foundation career workshops.',
 'Open to all undergraduate STEM students with a GPA of at least 1.75 and good moral character.',
 '2026-09-30', 'https://brightfuture-demo.example.com/apply/stem',
 'approved', NULL),
(2, 1, 'BrightFuture Community Leadership Scholarship',
 'A DEMO scholarship recognizing students who show outstanding community leadership and volunteer service.',
 'Leadership', 'Undergraduate', 'National', 'PHP 30,000 one-time',
 'One-time grant of PHP 30,000 and a leadership training program voucher.',
 'Open to undergraduate students with proven community service records and at least 2.00 GPA.',
 '2026-09-15', 'https://brightfuture-demo.example.com/apply/leadership',
 'approved', NULL),
(3, 1, 'BrightFuture Research Excellence Grant',
 'A DEMO grant proposal for supporting undergraduate research projects in science and engineering.',
 'Research Grant', 'Undergraduate', 'National', 'PHP 100,000',
 'Research funding up to PHP 100,000 and faculty mentorship.',
 'Open to undergraduate students who are part of an active research group.',
 '2026-10-20', 'https://brightfuture-demo.example.com/apply/research',
 'pending', NULL),
(4, 1, 'BrightFuture Arts and Culture Grant',
 'A DEMO grant for arts students. This record is rejected to demonstrate the rejection workflow.',
 'Merit-Based', 'Undergraduate', 'National', 'PHP 20,000',
 'One-time grant for arts and culture programs.',
 'Open to students in fine arts or performing arts programs.',
 '2026-08-30', 'https://brightfuture-demo.example.com/apply/arts',
 'rejected', 'The submitted documents were incomplete. Please provide a valid certificate of enrollment and a recommendation letter from a faculty adviser before resubmitting.'),
(5, 1, 'BrightFuture Medical Aspirants Grant',
 'A DEMO draft scholarship that has not been submitted for approval yet.',
 'Merit-Based', 'Undergraduate', 'National', 'PHP 40,000',
 'Annual grant for pre-med and nursing students.',
 'Open to students in medical-related programs with a GPA of 1.90 or better.',
 '2026-11-15', 'https://brightfuture-demo.example.com/apply/medical',
 'draft', NULL),
(6, 2, 'TechScholar Innovation Scholarship',
 'A DEMO scholarship for students building creative technology projects, apps, or hardware.',
 'Merit-Based', 'Undergraduate', 'National', 'PHP 60,000 per year',
 'Annual stipend of PHP 60,000 plus internship placement assistance.',
 'Open to IT, Computer Science, and Engineering students with a strong project portfolio.',
 '2026-10-31', 'https://techscholar-demo.example.com/apply/innovation',
 'approved', NULL),
(7, 2, 'TechScholar Women in Tech Scholarship',
 'A DEMO scholarship supporting women pursuing technology degrees.',
 'Diversity', 'Undergraduate', 'National', 'PHP 45,000 per year',
 'Annual stipend of PHP 45,000 and mentoring from industry professionals.',
 'Open to female students enrolled in any technology-related degree program.',
 '2026-08-20', 'https://techscholar-demo.example.com/apply/womenintech',
 'approved', NULL),
(8, 2, 'TechScholar Open Source Contributor Grant',
 'A DEMO grant for students who actively contribute to open source software.',
 'Skill-Based', 'College', 'Remote', 'PHP 25,000',
 'One-time grant for active open source contributors.',
 'Open to college students with verifiable open source contributions.',
 '2026-12-01', 'https://techscholar-demo.example.com/apply/opensource',
 'pending', NULL),
(9, 2, 'TechScholar Data Science Bootcamp Scholarship',
 'A DEMO scholarship covering tuition for a data science bootcamp program.',
 'Need-Based', 'Postgraduate', 'Remote', 'PHP 80,000',
 'Full bootcamp tuition support for one cohort.',
 'Open to graduates of any technology degree with demonstrated financial need.',
 '2026-10-15', 'https://techscholar-demo.example.com/apply/datascience',
 'approved', NULL),
(10, 1, 'BrightFuture Freshman Achiever Award',
 'A DEMO award for outstanding incoming first-year college students.',
 'Merit-Based', 'Undergraduate', 'National', 'PHP 25,000',
 'One-time award plus a welcome kit.',
 'Open to incoming first-year students with a high school average of 92 or above.',
 '2026-12-10', 'https://brightfuture-demo.example.com/apply/freshman',
 'approved', NULL),
(11, 2, 'TechScholar Legacy Program (Expired Demo)',
 'A DEMO approved scholarship whose deadline has already passed. It is treated as expired by the system.',
 'Merit-Based', 'Undergraduate', 'National', 'PHP 50,000',
 'Annual stipend for tech students.',
 'Open to technology students with a GPA of 1.75 or better.',
 '2026-06-30', 'https://techscholar-demo.example.com/apply/legacy',
 'approved', NULL);

-- =============================================================
-- DEMO SCHOLARSHIP REQUIREMENTS
-- =============================================================
INSERT INTO `scholarship_requirements` (`scholarship_id`, `requirement`) VALUES
(1, 'Must be enrolled in a STEM-related undergraduate program.'),
(1, 'General weighted average of at least 1.75.'),
(1, 'Must be in good standing with no failing grades.'),
(2, 'Must be an undergraduate student.'),
(2, 'Must submit proof of community service involvement.'),
(2, 'General weighted average of at least 2.00.'),
(3, 'Must be part of an active research group.'),
(3, 'Must have a faculty adviser endorsement.'),
(4, 'Must be enrolled in a fine arts or performing arts program.'),
(5, 'Must be enrolled in a pre-med or nursing program.'),
(5, 'General weighted average of at least 1.90.'),
(6, 'Must be enrolled in IT, Computer Science, or Engineering.'),
(6, 'Must submit a portfolio of personal technology projects.'),
(6, 'Must have a general weighted average of at least 2.00.'),
(7, 'Must be a female student.'),
(7, 'Must be enrolled in a technology-related degree program.'),
(8, 'Must be a college student.'),
(8, 'Must provide links to open source contributions.'),
(9, 'Must be a graduate of any technology degree.'),
(9, 'Must demonstrate financial need.'),
(10, 'Must be an incoming first-year college student.'),
(10, 'High school general average of 92 or above.'),
(11, 'Must be enrolled in a technology-related program.');

-- =============================================================
-- DEMO REQUIRED DOCUMENTS
-- =============================================================
INSERT INTO `required_documents` (`scholarship_id`, `document_name`, `description`) VALUES
(1, 'Certificate of Enrollment', 'Official enrollment certificate for the current semester.'),
(1, 'Transcript of Records', 'Latest official or certified copy.'),
(1, 'Good Moral Certificate', 'Issued by the school guidance office.'),
(2, 'Community Service Proof', 'Certificates, photos, or letters from organizations served.'),
(2, 'Certificate of Enrollment', 'Official enrollment certificate for the current semester.'),
(3, 'Research Proposal', 'A 3-page summary of the proposed research.'),
(3, 'Adviser Endorsement Letter', 'Signed by the faculty adviser.'),
(6, 'Project Portfolio', 'Links or files of personal technology projects.'),
(6, 'Certificate of Enrollment', 'Official enrollment certificate.'),
(7, 'Certificate of Enrollment', 'Official enrollment certificate.'),
(7, 'Gender Declaration', 'Any government-issued document confirming gender.'),
(8, 'GitHub or Contribution Links', 'Links to public open source contributions.'),
(9, 'Income Proof', 'Latest income tax return or certificate of indigency.'),
(9, 'Transcript of Records', 'Latest official or certified copy.'),
(10, 'High School Report Card', 'Grade 12 report card or transcript.'),
(10, 'Certificate of Enrollment', 'Proof of first-year enrollment for the incoming school year.');

-- =============================================================
-- DEMO SAVED SCHOLARSHIPS
-- =============================================================
INSERT INTO `saved_scholarships` (`student_id`, `scholarship_id`) VALUES
(1, 1),
(1, 6),
(2, 2),
(2, 7),
(3, 10);

-- =============================================================
-- DEMO APPLICATIONS (application tracking records)
-- =============================================================
INSERT INTO `applications` (`student_id`, `scholarship_id`, `status`, `application_date`, `notes`) VALUES
(1, 1, 'applied', '2026-08-01', 'Prepared all required documents. Waiting for a response from the provider.'),
(1, 6, 'under_review', '2026-08-05', 'Portfolio submitted. Provider is reviewing my project portfolio.'),
(2, 2, 'interested', '2026-08-08', 'Planning to gather community service certificates.'),
(2, 7, 'preparing', '2026-08-10', 'Still requesting a copy of my certificate of enrollment.'),
(3, 10, 'interested', '2026-08-12', 'Incoming freshman, gathering high school records.');

-- =============================================================
-- DEMO NOTIFICATIONS
-- =============================================================
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`) VALUES
(1, 'Welcome to EduGrant Admin', 'You are logged in as the administrator. Review pending scholarships from the admin dashboard.', 'system', 1),
(2, 'Welcome to EduGrant', 'Explore the scholarship directory to find opportunities that match your profile.', 'system', 0),
(2, 'Your saved scholarship deadline is approaching', 'BrightFuture STEM Excellence Scholarship deadline is approaching. Prepare your requirements.', 'deadline', 0),
(3, 'Welcome to EduGrant', 'Explore the scholarship directory and start tracking your applications.', 'system', 0),
(4, 'Welcome to EduGrant', 'Explore the scholarship directory and start tracking your applications.', 'system', 0),
(5, 'Scholarship approved', 'Your scholarship "BrightFuture STEM Excellence Scholarship" has been approved by the administrator.', 'approval', 1),
(5, 'Scholarship rejected', 'Your scholarship "BrightFuture Arts and Culture Grant" was rejected. See the rejection reason in your dashboard.', 'rejection', 0),
(6, 'Account created', 'Your provider account has been created. Await administrator verification.', 'system', 0),
(1, 'New provider registered', 'TechScholar Foundation (Demo Provider) registered and is awaiting verification.', 'system', 0);

-- =============================================================
-- DEMO ACTIVITY LOGS
-- =============================================================
INSERT INTO `activity_logs` (`user_id`, `action`, `description`) VALUES
(1, 'Registration', 'Demo admin account created.'),
(2, 'Registration', 'Demo student account created.'),
(3, 'Registration', 'Demo student account created.'),
(4, 'Registration', 'Demo student account created.'),
(5, 'Registration', 'Demo provider account created.'),
(6, 'Registration', 'Demo provider account created.'),
(5, 'Scholarship creation', 'Created scholarship "BrightFuture STEM Excellence Scholarship".'),
(5, 'Scholarship submission', 'Submitted "BrightFuture STEM Excellence Scholarship" for approval.'),
(1, 'Scholarship approval', 'Approved scholarship "BrightFuture STEM Excellence Scholarship".'),
(5, 'Scholarship rejection', 'Scholarship "BrightFuture Arts and Culture Grant" was rejected.'),
(2, 'Application creation', 'Started tracking application for "BrightFuture STEM Excellence Scholarship".'),
(2, 'Application status update', 'Updated application status to "applied" for "BrightFuture STEM Excellence Scholarship".');

SET FOREIGN_KEY_CHECKS = 1;

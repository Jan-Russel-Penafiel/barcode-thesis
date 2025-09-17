-- =======================================================
-- Barcode Attendance System Database Schema
-- Created: September 12, 2025
-- Description: Complete database structure for the barcode attendance system
-- =======================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `barcode_db` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `barcode_db`;

-- =======================================================
-- 1. USERS TABLE
-- Stores system administrator accounts
-- =======================================================
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `full_name` VARCHAR(100) DEFAULT NULL,
    `role` ENUM('admin', 'user') DEFAULT 'user',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`),
    INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 2. BARCODES TABLE
-- Stores student/employee barcode information
-- =======================================================
CREATE TABLE IF NOT EXISTS `barcodes` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `barcode` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `course_year` VARCHAR(10) NOT NULL,
    `student_id` VARCHAR(20) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_barcode` (`barcode`),
    INDEX `idx_name` (`name`),
    INDEX `idx_course` (`course`),
    INDEX `idx_course_year` (`course_year`),
    INDEX `idx_status` (`status`),
    INDEX `idx_student_id` (`student_id`),
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 3. ATTENDANCE TABLE
-- Stores daily attendance records
-- =======================================================
CREATE TABLE IF NOT EXISTS `attendance` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `barcode` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `course_year` VARCHAR(10) NOT NULL,
    `date` DATE NOT NULL,
    `day` VARCHAR(10) NOT NULL,
    `time_in` TIME DEFAULT NULL,
    `time_out` TIME DEFAULT NULL,
    `duration_minutes` INT(11) DEFAULT NULL,
    `status` ENUM('present', 'partial', 'absent') DEFAULT 'present',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_daily_attendance` (`barcode`, `date`),
    INDEX `idx_barcode` (`barcode`),
    INDEX `idx_date` (`date`),
    INDEX `idx_name` (`name`),
    INDEX `idx_course` (`course`),
    INDEX `idx_status` (`status`),
    INDEX `idx_time_in` (`time_in`),
    INDEX `idx_time_out` (`time_out`),
    FOREIGN KEY (`barcode`) REFERENCES `barcodes`(`barcode`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 4. AUDIT LOG TABLE
-- Tracks system activities and changes
-- =======================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(50) DEFAULT NULL,
    `record_id` INT(11) DEFAULT NULL,
    `old_values` JSON DEFAULT NULL,
    `new_values` JSON DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_table_name` (`table_name`),
    INDEX `idx_created_at` (`created_at`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 5. SETTINGS TABLE
-- Stores system configuration settings
-- =======================================================
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    `description` TEXT DEFAULT NULL,
    `is_editable` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =======================================================
-- 6. ATTENDANCE SUMMARY VIEW
-- Provides summary statistics for attendance
-- =======================================================
CREATE OR REPLACE VIEW `attendance_summary` AS
SELECT 
    b.barcode,
    b.name,
    b.course,
    b.course_year,
    b.status as barcode_status,
    COUNT(a.id) as total_days,
    SUM(CASE WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 1 ELSE 0 END) as complete_days,
    SUM(CASE WHEN a.time_in IS NOT NULL AND a.time_out IS NULL THEN 1 ELSE 0 END) as partial_days,
    SUM(CASE WHEN a.duration_minutes IS NOT NULL THEN a.duration_minutes ELSE 0 END) as total_minutes,
    MAX(a.date) as last_attendance_date,
    MIN(a.date) as first_attendance_date
FROM barcodes b
LEFT JOIN attendance a ON b.barcode = a.barcode
WHERE b.status = 'active'
GROUP BY b.barcode, b.name, b.course, b.course_year, b.status;

-- =======================================================
-- 7. DAILY ATTENDANCE REPORT VIEW
-- Shows attendance for a specific date
-- =======================================================
CREATE OR REPLACE VIEW `daily_attendance_report` AS
SELECT 
    a.date,
    a.day,
    b.barcode,
    b.name,
    b.course,
    b.course_year,
    a.time_in,
    a.time_out,
    a.duration_minutes,
    a.status,
    CASE 
        WHEN a.time_in IS NULL THEN 'Absent'
        WHEN a.time_in IS NOT NULL AND a.time_out IS NULL THEN 'Time In Only'
        WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 'Complete'
        ELSE 'Unknown'
    END as attendance_status
FROM barcodes b
LEFT JOIN attendance a ON b.barcode = a.barcode
WHERE b.status = 'active'
ORDER BY a.date DESC, b.name;

-- =======================================================
-- TRIGGERS
-- =======================================================

-- Trigger to automatically calculate duration when time_out is updated
DELIMITER //
CREATE TRIGGER `calculate_duration_on_timeout` 
BEFORE UPDATE ON `attendance`
FOR EACH ROW
BEGIN
    IF NEW.time_out IS NOT NULL AND NEW.time_in IS NOT NULL THEN
        SET NEW.duration_minutes = TIMESTAMPDIFF(MINUTE, 
            CONCAT(NEW.date, ' ', NEW.time_in), 
            CONCAT(NEW.date, ' ', NEW.time_out));
        
        -- Set status based on presence
        IF NEW.time_in IS NOT NULL AND NEW.time_out IS NOT NULL THEN
            SET NEW.status = 'present';
        ELSEIF NEW.time_in IS NOT NULL THEN
            SET NEW.status = 'partial';
        ELSE
            SET NEW.status = 'absent';
        END IF;
    END IF;
END//

-- Trigger to update last_login when user logs in
CREATE TRIGGER `update_last_login`
AFTER INSERT ON `audit_log`
FOR EACH ROW
BEGIN
    IF NEW.action = 'login' AND NEW.user_id IS NOT NULL THEN
        UPDATE users SET last_login = NEW.created_at WHERE id = NEW.user_id;
    END IF;
END//

DELIMITER ;

-- =======================================================
-- STORED PROCEDURES
-- =======================================================

-- Procedure to get attendance report for date range
DELIMITER //
CREATE PROCEDURE `GetAttendanceReport`(
    IN start_date DATE,
    IN end_date DATE,
    IN course_filter VARCHAR(50)
)
BEGIN
    SELECT 
        a.date,
        a.day,
        b.barcode,
        b.name,
        b.course,
        b.course_year,
        a.time_in,
        a.time_out,
        a.duration_minutes,
        a.status
    FROM attendance a
    JOIN barcodes b ON a.barcode = b.barcode
    WHERE a.date BETWEEN start_date AND end_date
    AND (course_filter IS NULL OR b.course = course_filter)
    ORDER BY a.date DESC, b.name;
END//

-- Procedure to get monthly attendance summary
CREATE PROCEDURE `GetMonthlyAttendanceSummary`(
    IN year_param INT,
    IN month_param INT
)
BEGIN
    SELECT 
        b.name,
        b.course,
        b.course_year,
        COUNT(a.id) as days_present,
        SUM(CASE WHEN a.time_in IS NOT NULL AND a.time_out IS NOT NULL THEN 1 ELSE 0 END) as complete_days,
        SUM(CASE WHEN a.duration_minutes IS NOT NULL THEN a.duration_minutes ELSE 0 END) as total_minutes,
        ROUND(AVG(a.duration_minutes), 2) as avg_minutes_per_day
    FROM barcodes b
    LEFT JOIN attendance a ON b.barcode = a.barcode 
        AND YEAR(a.date) = year_param 
        AND MONTH(a.date) = month_param
    WHERE b.status = 'active'
    GROUP BY b.barcode, b.name, b.course, b.course_year
    ORDER BY b.name;
END//

DELIMITER ;

-- =======================================================
-- DEFAULT DATA INSERTION
-- =======================================================

-- Insert default system settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('system_name', 'Barcode Attendance System', 'string', 'Name of the system'),
('timezone', 'Asia/Manila', 'string', 'System timezone'),
('attendance_grace_period', '15', 'integer', 'Grace period in minutes for late attendance'),
('max_daily_hours', '12', 'integer', 'Maximum hours per day'),
('email_notifications', 'true', 'boolean', 'Enable email notifications'),
('backup_frequency', 'daily', 'string', 'Database backup frequency'),
('session_timeout', '3600', 'integer', 'Session timeout in seconds'),
('date_format', 'Y-m-d', 'string', 'Default date format'),
('time_format', 'H:i:s', 'string', 'Default time format')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    updated_at = CURRENT_TIMESTAMP;

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `email`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'admin@example.com')
ON DUPLICATE KEY UPDATE 
    updated_at = CURRENT_TIMESTAMP;

-- =======================================================
-- INDEXES FOR PERFORMANCE
-- =======================================================

-- Additional composite indexes for better query performance
CREATE INDEX `idx_attendance_date_barcode` ON `attendance` (`date`, `barcode`);
CREATE INDEX `idx_attendance_course_date` ON `attendance` (`course`, `date`);
CREATE INDEX `idx_barcodes_course_year_status` ON `barcodes` (`course`, `course_year`, `status`);

-- =======================================================
-- DATABASE CONSTRAINTS AND VALIDATIONS
-- =======================================================

-- Add check constraints (MySQL 8.0+)
-- ALTER TABLE `barcodes` ADD CONSTRAINT `chk_barcode_length` 
-- CHECK (CHAR_LENGTH(`barcode`) >= 10 AND CHAR_LENGTH(`barcode`) <= 20);

-- ALTER TABLE `attendance` ADD CONSTRAINT `chk_time_order` 
-- CHECK (`time_out` IS NULL OR `time_in` IS NULL OR `time_out` > `time_in`);

-- =======================================================
-- BACKUP AND MAINTENANCE PROCEDURES
-- =======================================================

-- Procedure to archive old attendance records (older than 2 years)
DELIMITER //
CREATE PROCEDURE `ArchiveOldAttendance`()
BEGIN
    DECLARE archive_date DATE;
    SET archive_date = DATE_SUB(CURDATE(), INTERVAL 2 YEAR);
    
    -- Create archive table if it doesn't exist
    CREATE TABLE IF NOT EXISTS `attendance_archive` LIKE `attendance`;
    
    -- Move old records to archive
    INSERT INTO `attendance_archive` 
    SELECT * FROM `attendance` 
    WHERE `date` < archive_date;
    
    -- Delete old records from main table
    DELETE FROM `attendance` 
    WHERE `date` < archive_date;
    
    SELECT CONCAT('Archived attendance records older than ', archive_date) AS result;
END//

DELIMITER ;

-- =======================================================
-- SAMPLE DATA FOR TESTING (Optional)
-- =======================================================

-- Uncomment the following section if you want to insert sample data for testing

/*
-- Sample barcodes
INSERT INTO `barcodes` (`barcode`, `name`, `course`, `course_year`, `student_id`, `email`, `status`) VALUES
('17556743189659', 'John Doe', 'BSIT', '2', 'ST2023001', 'john.doe@example.com', 'active'),
('17557402804336', 'Jane Smith', 'BSIS', '3', 'ST2022001', 'jane.smith@example.com', 'active'),
('17557635642763', 'Mike Johnson', 'BSCS', '1', 'ST2024001', 'mike.johnson@example.com', 'active');

-- Sample attendance records
INSERT INTO `attendance` (`barcode`, `name`, `course`, `course_year`, `date`, `day`, `time_in`, `time_out`) VALUES
('17556743189659', 'John Doe', 'BSIT', '2', '2025-09-12', 'Thursday', '08:30:00', '17:00:00'),
('17557402804336', 'Jane Smith', 'BSIS', '3', '2025-09-12', 'Thursday', '09:00:00', '16:30:00'),
('17557635642763', 'Mike Johnson', 'BSCS', '1', '2025-09-12', 'Thursday', '08:45:00', NULL);
*/

-- =======================================================
-- END OF SQL FILE
-- =======================================================

-- Instructions for use:
-- 1. Run this SQL file to create the complete database structure
-- 2. The existing migrate_json_to_mysql.php can be used to import data from data.json
-- 3. Update your PHP files to use the new database structure
-- 4. Consider adding proper error handling and validation in your PHP code
-- 5. Regularly backup your database using the provided procedures

-- Version: 1.0
-- Last Updated: September 12, 2025
USE tutor_booking;

-- Users: admin role + approval status
ALTER TABLE users MODIFY COLUMN role ENUM('student', 'tutor', 'admin') NOT NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('pending', 'active', 'rejected', 'suspended') NOT NULL DEFAULT 'active' AFTER role;
UPDATE users SET status = 'active' WHERE status IS NULL OR status = '';

-- Categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student profiles & questionnaire
CREATE TABLE IF NOT EXISTS student_profiles (
    user_id INT PRIMARY KEY,
    about_me TEXT,
    education_level ENUM('high_school', 'undergraduate', 'graduate', 'professional', 'other') DEFAULT 'other',
    learning_goals TEXT,
    preferred_mode ENUM('online', 'in_person', 'both') DEFAULT 'both',
    questionnaire_completed TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS student_categories (
    student_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (student_id, category_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Tutor profile extensions
ALTER TABLE tutors ADD COLUMN IF NOT EXISTS qualifications TEXT;
ALTER TABLE tutors ADD COLUMN IF NOT EXISTS experience_years INT NOT NULL DEFAULT 0;
ALTER TABLE tutors ADD COLUMN IF NOT EXISTS phone VARCHAR(30) DEFAULT NULL;
ALTER TABLE tutors ADD COLUMN IF NOT EXISTS contact_email VARCHAR(120) DEFAULT NULL;
ALTER TABLE tutors ADD COLUMN IF NOT EXISTS portfolio_url VARCHAR(255) DEFAULT NULL;
ALTER TABLE tutors ADD COLUMN IF NOT EXISTS profile_image VARCHAR(255) DEFAULT NULL;
ALTER TABLE tutors ADD COLUMN IF NOT EXISTS teaches_levels VARCHAR(120) DEFAULT 'high_school,undergraduate,graduate';
ALTER TABLE tutors ADD COLUMN IF NOT EXISTS class_details TEXT;

CREATE TABLE IF NOT EXISTS tutor_categories (
    tutor_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (tutor_id, category_id),
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tutor_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    class_level VARCHAR(80) DEFAULT NULL,
    description TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    join_link VARCHAR(255) DEFAULT NULL,
    contact_phone VARCHAR(30) DEFAULT NULL,
    contact_email VARCHAR(120) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tutor_availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tutor_id INT NOT NULL,
    day_of_week TINYINT NOT NULL COMMENT '0=Sun, 6=Sat',
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (tutor_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bookings with time
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS booking_time TIME DEFAULT NULL;
ALTER TABLE bookings ADD COLUMN IF NOT EXISTS notes TEXT;

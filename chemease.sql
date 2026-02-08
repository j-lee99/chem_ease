-- ChemEase Database Schema
-- Drop existing tables if they exist (in reverse dependency order)
DROP TABLE IF EXISTS user_exam_responses;
DROP TABLE IF EXISTS user_exam_attempts;
DROP TABLE IF EXISTS exam_answers;
DROP TABLE IF EXISTS exam_questions;
DROP TABLE IF EXISTS exams;
DROP TABLE IF EXISTS user_progress;
DROP TABLE IF EXISTS study_material_files;
DROP TABLE IF EXISTS study_materials;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    -- address VARCHAR(255) NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Study materials main table
CREATE TABLE study_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM(
        'Analytical Chemistry',
        'Organic Chemistry',
        'Physical Chemistry',
        'Inorganic Chemistry',
        'BioChemistry'
    ) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Study material files (one-to-many relationship with study_materials)
CREATE TABLE study_material_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    material_id INT NOT NULL,
    type ENUM('pdf', 'video') NOT NULL,
    path VARCHAR(500) NOT NULL,
    FOREIGN KEY (material_id) REFERENCES study_materials(id) ON DELETE CASCADE
);

-- User progress tracking per file
CREATE TABLE user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_id INT NOT NULL,
    progress INT DEFAULT 0,
    UNIQUE KEY uniq_user_file (user_id, file_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (file_id) REFERENCES study_material_files(id) ON DELETE CASCADE
);

-- Exams table
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM(
        'Analytical Chemistry',
        'Organic Chemistry',
        'Physical Chemistry',
        'Inorganic Chemistry',
        'BioChemistry'
    ) NOT NULL,
    difficulty ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
    total_questions INT NOT NULL,
    duration_minutes INT NOT NULL,
    passing_score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Exam questions
CREATE TABLE exam_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    type ENUM('multiple', 'truefalse') NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Exam answer choices
CREATE TABLE exam_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text VARCHAR(500) NOT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE
);

-- User exam attempts
CREATE TABLE user_exam_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    finished_at TIMESTAMP NULL,
    score INT NULL,
    total_correct INT NULL,
    total_answered INT NULL,
    UNIQUE KEY uniq_user_exam (user_id, exam_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- User responses to individual questions
CREATE TABLE user_exam_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer_id INT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES user_exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES exam_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_answer_id) REFERENCES exam_answers(id) ON DELETE SET NULL
);
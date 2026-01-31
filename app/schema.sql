-- Tables pour les postes employeur et candidatures
-- Exécuter une fois pour créer la structure

CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employer_id INT DEFAULT 1,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS job_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    question_text VARCHAR(500) NOT NULL,
    sort_order TINYINT NOT NULL DEFAULT 1,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    UNIQUE KEY (job_id, sort_order)
);

CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    candidate_email VARCHAR(255) NOT NULL,
    candidate_name VARCHAR(255),
    video_url VARCHAR(500),
    status ENUM('new','viewed','accepted','rejected') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
);

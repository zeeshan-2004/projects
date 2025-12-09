-- === sql/feature_upgrade.sql ===

-- 1. Add priority to tasks
ALTER TABLE tasks ADD COLUMN priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium';

-- 2. Create activity_logs table
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NULL,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_task_log FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
);

-- 3. Create attachments table
CREATE TABLE IF NOT EXISTS attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime VARCHAR(100),
    size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_task_attach FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE
);

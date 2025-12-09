-- === sql/category_upgrade.sql ===

-- 1. Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- 2. Insert default categories
INSERT INTO categories (name) VALUES ('Development'), ('Study');

-- 3. Add category_id to tasks
-- Using IF NOT EXISTS logic via a stored procedure approach or just ignoring error if column exists is hard in pure SQL script without knowing version.
-- Standard approach:
ALTER TABLE tasks ADD COLUMN category_id INT NULL;

-- 4. Add foreign key constraint
ALTER TABLE tasks
ADD CONSTRAINT fk_task_category
FOREIGN KEY (category_id) REFERENCES categories(id)
ON DELETE SET NULL;

-- === sql/date_status_upgrade.sql ===

-- 1. Add start_date and due_date columns
ALTER TABLE tasks
ADD COLUMN start_date DATE NULL AFTER description,
ADD COLUMN due_date DATE NULL AFTER start_date;

-- 2. Modify status column to include 'in_progress'
-- Note: Modifying an ENUM in MySQL usually requires redefining the whole column.
ALTER TABLE tasks
MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending';

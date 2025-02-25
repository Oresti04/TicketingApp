CREATE DATABASE IF NOT EXISTS ticketing_app;
USE ticketing_app;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tickets Table
CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Gamification Table
CREATE TABLE IF NOT EXISTS gamification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT DEFAULT 0,
    achievements TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample data into users table
INSERT INTO users (username, email, password_hash) VALUES
('john_doe', 'john@example.com', 'hashedpassword1'),
('jane_smith', 'jane@example.com', 'hashedpassword2'),
('alice_jones', 'alice@example.com', 'hashedpassword3'),
('bob_brown', 'bob@example.com', 'hashedpassword4'),
('charlie_black', 'charlie@example.com', 'hashedpassword5');

-- Insert sample data into tickets table
INSERT INTO tickets (user_id, subject, description, status) VALUES
(1, 'Login Issue', 'Unable to log in with correct credentials', 'open'),
(2, 'Payment Problem', 'Transaction failed but money was deducted', 'in_progress'),
(3, 'Bug Report', 'Page crashes when clicking submit', 'open'),
(4, 'Feature Request', 'Add dark mode to the UI', 'closed'),
(5, 'Account Recovery', 'Forgot password and recovery email not received', 'open');

-- Insert sample data into gamification table
INSERT INTO gamification (user_id, points, achievements) VALUES
(1, 100, 'First Ticket Submitted'),
(2, 250, 'Helped Another User'),
(3, 50, 'Bug Reported'),
(4, 300, 'Most Active User'),
(5, 200, 'Feature Suggested');

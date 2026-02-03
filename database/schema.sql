-- Kişisel Dashboard Veritabanı Şeması
-- MySQL/MariaDB

CREATE DATABASE IF NOT EXISTS personal_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE personal_dashboard;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Kullanıcı ayarları tablosu
CREATE TABLE IF NOT EXISTS user_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    setting_key VARCHAR(50) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_setting (user_id, setting_key)
);

-- Ödemeler tablosu
CREATE TABLE IF NOT EXISTS payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'TRY',
    due_date DATE NOT NULL,
    category VARCHAR(50),
    status ENUM('pending', 'paid', 'overdue') DEFAULT 'pending',
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_period ENUM('weekly', 'monthly', 'yearly') DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Takvim olayları tablosu
CREATE TABLE IF NOT EXISTS calendar_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    event_type ENUM('birthday', 'anniversary', 'meeting', 'reminder', 'holiday', 'other') DEFAULT 'other',
    color VARCHAR(7) DEFAULT '#6366f1',
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_period ENUM('weekly', 'monthly', 'yearly') DEFAULT NULL,
    reminder_before INT DEFAULT NULL, -- dakika cinsinden
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notlar tablosu
CREATE TABLE IF NOT EXISTS notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    content TEXT,
    color VARCHAR(7) DEFAULT '#fbbf24',
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Yapılacaklar listesi tablosu
CREATE TABLE IF NOT EXISTS todos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    due_date DATE DEFAULT NULL,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Varsayılan admin kullanıcısı (şifre: admin123 - üretimde değiştirin!)
-- Şifre hash'i password_hash('admin123', PASSWORD_DEFAULT) ile oluşturulmalı
INSERT INTO users (username, email, password, full_name) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User');

-- Varsayılan ayarlar
INSERT INTO user_settings (user_id, setting_key, setting_value) VALUES
(1, 'weather_city', 'Istanbul'),
(1, 'weather_country', 'TR'),
(1, 'theme', 'dark'),
(1, 'language', 'tr');

-- İndeksler
CREATE INDEX idx_payments_user_date ON payments(user_id, due_date);
CREATE INDEX idx_calendar_user_date ON calendar_events(user_id, event_date);
CREATE INDEX idx_notes_user_pinned ON notes(user_id, is_pinned);
CREATE INDEX idx_todos_user_status ON todos(user_id, status);

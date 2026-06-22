-- Desire Thermometer — Database Setup
-- Run: mysql -u root -p < setup.sql

CREATE DATABASE IF NOT EXISTS desire_thermometer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE desire_thermometer;

-- Users
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name    VARCHAR(50)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Per-user custom names for desire levels 1–10
CREATE TABLE IF NOT EXISTS desire_scale (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    level   TINYINT UNSIGNED NOT NULL,
    name    VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_user_level (user_id, level),
    CONSTRAINT fk_ds_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Partnership requests between users
CREATE TABLE IF NOT EXISTS partnerships (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requester_id INT UNSIGNED NOT NULL,
    receiver_id  INT UNSIGNED NOT NULL,
    status       ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_part_req FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_part_rec FOREIGN KEY (receiver_id)  REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- History of desire levels set by each user
CREATE TABLE IF NOT EXISTS desire_history (
    id      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    level   TINYINT UNSIGNED NOT NULL,
    set_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_dh_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

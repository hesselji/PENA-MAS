-- Jalankan SATU KALI pada database PENA MAS yang sudah ada.
-- Aman dijalankan ulang karena menggunakan CREATE TABLE IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(60) NOT NULL,
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    url VARCHAR(255) NULL,
    entity_type VARCHAR(60) NULL,
    entity_id BIGINT UNSIGNED NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user_created (user_id, created_at),
    INDEX idx_notifications_user_read (user_id, read_at),
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

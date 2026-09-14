-- PENA MAS - Revisi hasil konsultasi Pak Citra (10 Sep 2026)
-- Jalankan pada database v0.4 yang SUDAH memiliki deleted_at/full KKA.
-- Untuk database baru/fresh, cukup import database/mysql_schema_seed.sql.

SET NAMES utf8mb4;

ALTER TABLE users
    MODIFY COLUMN role ENUM('ADMIN','USER','INCOMING') NOT NULL DEFAULT 'USER';

-- 'Penting' tidak lagi ditawarkan. Data historis tetap kompatibel jika pernah ada.
UPDATE letter_sensitivities
SET is_active = 0
WHERE code = 'PENTING';

-- Lepaskan email akun yang sudah soft-deleted oleh versi lama agar dapat dipakai ulang.
UPDATE users
SET email = CONCAT('deleted+', id, '+', UNIX_TIMESTAMP(COALESCE(deleted_at, NOW())), '@penamas.invalid')
WHERE deleted_at IS NOT NULL
  AND email NOT LIKE 'deleted+%@penamas.invalid';

CREATE TABLE IF NOT EXISTS incoming_letters (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    letter_number VARCHAR(190) NOT NULL,
    origin VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    recipient VARCHAR(255) NOT NULL,
    letter_date DATE NOT NULL,
    received_date DATE NOT NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_incoming_search(received_date,letter_date),
    INDEX idx_incoming_creator(created_by),
    CONSTRAINT fk_incoming_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Sequence 62710 dan 62711 SUDAH terpisah oleh numbering_rule_id + year
-- pada tabel number_sequences versi sebelumnya, jadi tidak perlu ALTER table.

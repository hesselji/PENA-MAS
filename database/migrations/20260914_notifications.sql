CREATE TABLE notification_states (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    outgoing_letter_id BIGINT UNSIGNED NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_notification_user_letter (
        user_id,
        outgoing_letter_id
    ),

    INDEX idx_notification_user (
        user_id,
        is_read,
        deleted_at
    ),

    CONSTRAINT fk_notification_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notification_letter
        FOREIGN KEY (outgoing_letter_id)
        REFERENCES outgoing_letters(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
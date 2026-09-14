-- PENA MAS - User account management
-- Run ONCE on an existing database that does not yet have users.deleted_at.

ALTER TABLE users
    ADD COLUMN deleted_at DATETIME NULL AFTER is_active,
    ADD INDEX idx_users_deleted_at (deleted_at);

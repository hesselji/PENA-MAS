-- PENA MAS - mentor revisions and UI refinements (2026-10-13)
-- Jalankan setelah migration 20261002_prototype_suite.sql.
SET FOREIGN_KEY_CHECKS=0;

-- Workflow surat masuk: MASUK -> DISPOSISI -> SELESAI.
UPDATE incoming_letters SET workflow_status='MASUK' WHERE workflow_status='BARU';
UPDATE incoming_letters SET workflow_status='DISPOSISI' WHERE workflow_status='DIPROSES';
UPDATE incoming_letter_events SET status_to='MASUK' WHERE status_to='BARU';
UPDATE incoming_letter_events SET status_to='DISPOSISI' WHERE status_to='DIPROSES';
ALTER TABLE incoming_letters MODIFY workflow_status ENUM('MASUK','DISPOSISI','SELESAI') NOT NULL DEFAULT 'MASUK';

CREATE TABLE incoming_letter_dispositions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incoming_letter_id BIGINT UNSIGNED NOT NULL,
    team_id INT UNSIGNED NOT NULL,
    assigned_user_id INT UNSIGNED NULL,
    note TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_incoming_disposition_team (incoming_letter_id, team_id),
    INDEX idx_incoming_disposition_team (team_id, assigned_user_id),
    CONSTRAINT fk_incoming_disposition_letter FOREIGN KEY(incoming_letter_id) REFERENCES incoming_letters(id) ON DELETE CASCADE,
    CONSTRAINT fk_incoming_disposition_team FOREIGN KEY(team_id) REFERENCES work_teams(id),
    CONSTRAINT fk_incoming_disposition_user FOREIGN KEY(assigned_user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_incoming_disposition_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- Keep the existing single-team workflow data visible after migration.
INSERT INTO incoming_letter_dispositions(incoming_letter_id,team_id,assigned_user_id,note,created_by,created_at,updated_at)
SELECT id,assigned_team_id,assigned_user_id,follow_up_note,COALESCE(status_updated_by,created_by),COALESCE(status_updated_at,created_at),COALESCE(status_updated_at,updated_at)
FROM incoming_letters
WHERE assigned_team_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM incoming_letter_dispositions d WHERE d.incoming_letter_id=incoming_letters.id AND d.team_id=incoming_letters.assigned_team_id);

-- One final batch groups the documents signed in one signing action.
CREATE TABLE signed_document_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(180) NULL,
    created_by INT UNSIGNED NOT NULL,
    document_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_signed_batch_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE generated_documents
    ADD COLUMN signature_batch_id BIGINT UNSIGNED NULL AFTER batch_id,
    ADD INDEX idx_generated_signature_batch (signature_batch_id),
    ADD CONSTRAINT fk_generated_signature_batch FOREIGN KEY(signature_batch_id) REFERENCES signed_document_batches(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS=1;

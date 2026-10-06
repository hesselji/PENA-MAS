SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE incoming_letters
    ADD COLUMN workflow_status ENUM('BARU','DIPROSES','SELESAI') NOT NULL DEFAULT 'BARU' AFTER notes,
    ADD COLUMN assigned_team_id INT UNSIGNED NULL AFTER workflow_status,
    ADD COLUMN assigned_user_id INT UNSIGNED NULL AFTER assigned_team_id,
    ADD COLUMN follow_up_note TEXT NULL AFTER assigned_user_id,
    ADD COLUMN status_updated_at DATETIME NULL AFTER follow_up_note,
    ADD COLUMN status_updated_by INT UNSIGNED NULL AFTER status_updated_at,
    ADD INDEX idx_incoming_workflow (workflow_status, assigned_team_id, assigned_user_id),
    ADD CONSTRAINT fk_incoming_assigned_team FOREIGN KEY(assigned_team_id) REFERENCES work_teams(id),
    ADD CONSTRAINT fk_incoming_assigned_user FOREIGN KEY(assigned_user_id) REFERENCES users(id),
    ADD CONSTRAINT fk_incoming_status_user FOREIGN KEY(status_updated_by) REFERENCES users(id);

CREATE TABLE incoming_letter_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    incoming_letter_id BIGINT UNSIGNED NOT NULL,
    event_type VARCHAR(40) NOT NULL,
    status_from VARCHAR(20) NULL,
    status_to VARCHAR(20) NULL,
    assigned_team_id INT UNSIGNED NULL,
    assigned_user_id INT UNSIGNED NULL,
    note TEXT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_incoming_event_letter (incoming_letter_id, created_at),
    CONSTRAINT fk_incoming_event_letter FOREIGN KEY(incoming_letter_id) REFERENCES incoming_letters(id) ON DELETE CASCADE,
    CONSTRAINT fk_incoming_event_team FOREIGN KEY(assigned_team_id) REFERENCES work_teams(id),
    CONSTRAINT fk_incoming_event_user FOREIGN KEY(assigned_user_id) REFERENCES users(id),
    CONSTRAINT fk_incoming_event_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE signers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    position VARCHAR(190) NOT NULL,
    nip VARCHAR(80) NULL,
    work_team_id INT UNSIGNED NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_signer_active (is_active, name),
    CONSTRAINT fk_signer_team FOREIGN KEY(work_team_id) REFERENCES work_teams(id),
    CONSTRAINT fk_signer_creator FOREIGN KEY(created_by) REFERENCES users(id),
    CONSTRAINT fk_signer_updater FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE document_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    letter_type_id INT UNSIGNED NULL,
    description VARCHAR(255) NULL,
    content_html LONGTEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    updated_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_template_active (is_active, letter_type_id),
    CONSTRAINT fk_template_letter_type FOREIGN KEY(letter_type_id) REFERENCES letter_types(id),
    CONSTRAINT fk_template_creator FOREIGN KEY(created_by) REFERENCES users(id),
    CONSTRAINT fk_template_updater FOREIGN KEY(updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE document_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(180) NULL,
    created_by INT UNSIGNED NOT NULL,
    document_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_document_batch_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE generated_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    batch_id BIGINT UNSIGNED NULL,
    outgoing_letter_id BIGINT UNSIGNED NOT NULL,
    template_id INT UNSIGNED NOT NULL,
    signer_id INT UNSIGNED NOT NULL,
    snapshot_html LONGTEXT NOT NULL,
    status ENUM('READY','PRINTED') NOT NULL DEFAULT 'READY',
    tte_status ENUM('NOT_CONNECTED','PENDING','SIGNED','FAILED') NOT NULL DEFAULT 'NOT_CONNECTED',
    tte_reference VARCHAR(255) NULL,
    created_by INT UNSIGNED NOT NULL,
    printed_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_generated_batch (batch_id),
    INDEX idx_generated_letter (outgoing_letter_id, created_at),
    INDEX idx_generated_status (status, tte_status),
    CONSTRAINT fk_generated_batch FOREIGN KEY(batch_id) REFERENCES document_batches(id) ON DELETE SET NULL,
    CONSTRAINT fk_generated_letter FOREIGN KEY(outgoing_letter_id) REFERENCES outgoing_letters(id),
    CONSTRAINT fk_generated_template FOREIGN KEY(template_id) REFERENCES document_templates(id),
    CONSTRAINT fk_generated_signer FOREIGN KEY(signer_id) REFERENCES signers(id),
    CONSTRAINT fk_generated_creator FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT INTO document_templates
(name, letter_type_id, description, content_html, is_active, created_by, updated_by)
VALUES
(
    'Template Surat Umum',
    NULL,
    'Template awal yang dapat disesuaikan Administrator.',
    '<div class="doc-letter"><div class="doc-head"><strong>BADAN PUSAT STATISTIK KOTA PALANGKA RAYA</strong></div><div class="doc-meta"><p>Nomor: {{NOMOR_SURAT}}</p><p>Tanggal: {{TANGGAL_SURAT}}</p></div><p>Yth. {{TUJUAN}}</p><p><strong>Perihal: {{PERIHAL}}</strong></p><div class="doc-body"><p>{{CATATAN}}</p></div><div class="doc-sign"><p>{{JABATAN_PENANDA_TANGAN}}</p><br><br><br><strong>{{NAMA_PENANDA_TANGAN}}</strong><br><span>{{NIP_PENANDA_TANGAN}}</span></div></div>',
    1,
    NULL,
    NULL
);

SET FOREIGN_KEY_CHECKS=1;

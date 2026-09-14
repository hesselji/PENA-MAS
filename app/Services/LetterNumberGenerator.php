<?php
namespace App\Services;

use App\Core\Database;

final class LetterNumberGenerator
{
    /**
     * Generate satu nomor surat. Method ini dipertahankan agar kompatibel
     * dengan pemanggilan lama, tetapi engine utamanya memakai generateBatch().
     */
    public function generate(array $input): array
    {
        $results = $this->generateBatch($input, [(string)($input['recipient'] ?? '')]);
        return $results[0];
    }

    /**
     * Mengalokasikan banyak nomor dalam SATU transaksi dan SATU row lock.
     *
     * Contoh: current_number=120 dan ada 45 tujuan -> dialokasikan 121..165.
     * Jika satu INSERT gagal, seluruh batch di-rollback sehingga sequence
     * tidak meloncat dan tidak ada batch setengah tersimpan.
     *
     * @return array<int,array{id:int,agenda_number:string,letter_number:string,letter_type_name:string,rule_code:string,recipient:string}>
     */
    public function generateBatch(array $input, array $recipients): array
    {
        $recipients = array_values(array_filter(array_map(
            static fn($value) => trim((string)$value),
            $recipients
        ), static fn($value) => $value !== ''));

        if (!$recipients) {
            throw new \InvalidArgumentException('Minimal satu tujuan surat wajib diisi.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT lt.id,lt.name,nr.id rule_id,nr.code rule_code,nr.unit_code,nr.pattern
                 FROM letter_types lt
                 JOIN numbering_rules nr ON nr.id=lt.numbering_rule_id
                 WHERE lt.id=? AND lt.is_active=1 AND nr.is_active=1
                 FOR UPDATE'
            );
            $stmt->execute([(int)$input['letter_type_id']]);
            $type = $stmt->fetch();
            if (!$type) {
                throw new \RuntimeException('Jenis surat atau aturan penomoran tidak aktif.');
            }

            $stmt = $pdo->prepare('SELECT prefix FROM letter_sensitivities WHERE code=? AND is_active=1');
            $stmt->execute([$input['sensitivity']]);
            $prefix = $stmt->fetchColumn();
            if ($prefix === false) {
                throw new \RuntimeException('Sifat surat tidak valid.');
            }

            $year = (int)date('Y', strtotime($input['letter_date']));

            // Satu sequence per numbering rule + tahun. 62710 dan 62711 tetap terpisah.
            $ensure = $pdo->prepare(
                'INSERT INTO number_sequences (numbering_rule_id,year,current_number,created_at,updated_at)
                 VALUES (?,?,0,NOW(),NOW())
                 ON DUPLICATE KEY UPDATE updated_at=updated_at'
            );
            $ensure->execute([(int)$type['rule_id'],$year]);

            // Hanya satu row sequence yang di-lock untuk seluruh batch.
            $seqStmt = $pdo->prepare(
                'SELECT id,current_number
                 FROM number_sequences
                 WHERE numbering_rule_id=? AND year=?
                 FOR UPDATE'
            );
            $seqStmt->execute([(int)$type['rule_id'],$year]);
            $sequence = $seqStmt->fetch();
            if (!$sequence) {
                throw new \RuntimeException('Sequence nomor tidak dapat dibuat.');
            }

            $current = (int)$sequence['current_number'];
            $batchSize = count($recipients);
            $finalSequence = $current + $batchSize;
            $kka = $input['classification_parent'].'.'.$input['classification_child'];

            $insert = $pdo->prepare(
                "INSERT INTO outgoing_letters
                (agenda_number,letter_number,year,letter_type_id,work_team_id,system_type,letter_date,sensitivity,uses_budget,archive_type,scope_key,classification_parent,classification_child,classification_code,recipient,subject,notes,status,requested_by,created_at,updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'ACTIVE',?,NOW(),NOW())"
            );

            $results = [];
            foreach ($recipients as $offset => $recipient) {
                $next = $current + $offset + 1;
                $seq3 = str_pad((string)$next,3,'0',STR_PAD_LEFT);
                $seq4 = str_pad((string)$next,4,'0',STR_PAD_LEFT);

                $tokens = [
                    '{PREFIX}' => (string)$prefix,
                    '{SEQ}' => (string)$next,
                    '{SEQ3}' => $seq3,
                    '{SEQ4}' => $seq4,
                    '{UNIT}' => (string)$type['unit_code'],
                    '{KKA}' => $kka,
                    '{YEAR}' => (string)$year,
                ];
                $number = strtr((string)$type['pattern'],$tokens);

                if (preg_match('/\{[A-Z0-9_]+\}/',$number,$match)) {
                    throw new \RuntimeException('Token aturan penomoran belum didukung: '.$match[0]);
                }

                $insert->execute([
                    $seq3,
                    $number,
                    $year,
                    (int)$input['letter_type_id'],
                    (int)$input['work_team_id'],
                    $input['system_type'],
                    $input['letter_date'],
                    $input['sensitivity'],
                    $input['uses_budget'],
                    $input['archive_type'],
                    $input['scope_key'],
                    $input['classification_parent'],
                    $input['classification_child'],
                    $kka,
                    $recipient,
                    $input['subject'],
                    $input['notes'] ?: null,
                    (int)$input['requested_by'],
                ]);

                $results[] = [
                    'id' => (int)$pdo->lastInsertId(),
                    'agenda_number' => $seq3,
                    'letter_number' => $number,
                    'letter_type_name' => $type['name'],
                    'rule_code' => $type['rule_code'],
                    'recipient' => $recipient,
                ];
            }

            // Sequence dinaikkan sekali saja ke nomor terakhir batch.
            $pdo->prepare('UPDATE number_sequences SET current_number=?,updated_at=NOW() WHERE id=?')
                ->execute([$finalSequence,(int)$sequence['id']]);

            $pdo->commit();
            return $results;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}

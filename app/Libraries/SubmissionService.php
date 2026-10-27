<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\Submissions;
use InvalidArgumentException;

/**
 * Public inquiries and diaspora submissions: storing them, notifying staff
 * and handling them in the inboxes (brief sections 2 and 3).
 *
 * Audit entries and notification emails carry the submission type and
 * reference only, never the visitor's personal details (PD-06).
 */
class SubmissionService
{
    private const REFERENCE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(
        private readonly BaseConnection $db,
        private readonly Submissions $config,
        private readonly AuditLog $audit,
        private readonly MailQueue $mail,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function type(string $type): array
    {
        return $this->config->types[$type] ?? throw new InvalidArgumentException("Unknown submission type {$type}.");
    }

    /**
     * Stores a submission and queues staff notifications.
     *
     * @param array<string, mixed> $data Validated fields
     *
     * @return array{id: int, reference: string}
     */
    public function create(string $type, array $data, string $locale): array
    {
        $definition = $this->type($type);
        $now        = Time::now('UTC')->toDateTimeString();
        $reference  = $this->newReference($definition);

        $this->db->table($definition['table'])->insert($data + [
            'reference'  => $reference,
            'locale'     => $locale,
            'status'     => array_key_first($this->config->statuses),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $this->db->insertID();

        $this->audit->record('submission.received', null, $type, $id, ['reference' => $reference]);
        $this->notify($type, $id, $reference);

        return ['id' => $id, 'reference' => $reference];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $type, int $id): ?array
    {
        return $this->db->table($this->type($type)['table'])->where('id', $id)->get()->getRowArray();
    }

    /**
     * @param array{status?: string, assigned_to?: int} $filters
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function list(string $type, array $filters, int $page, int $perPage): array
    {
        $builder = $this->db->table($this->type($type)['table']);

        if (isset($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (isset($filters['assigned_to'])) {
            $builder->where('assigned_to', $filters['assigned_to']);
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('created_at', 'DESC')->orderBy('id', 'DESC')->limit($perPage, ($page - 1) * $perPage)->get()->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }

    public function recordView(string $type, int $id, int $userId): void
    {
        $this->audit->record('submission.viewed', $userId, $type, $id);
    }

    public function changeStatus(string $type, int $id, string $status, int $userId): void
    {
        if (! isset($this->config->statuses[$status])) {
            throw new InvalidArgumentException('Unknown status.');
        }

        $this->touch($type, $id, ['status' => $status]);
        $this->audit->record('submission.status-changed', $userId, $type, $id, ['status' => $status]);
    }

    public function assign(string $type, int $id, ?int $assigneeId, int $userId): void
    {
        $this->touch($type, $id, ['assigned_to' => $assigneeId]);
        $this->audit->record('submission.assigned', $userId, $type, $id, ['assigned_to' => $assigneeId]);
    }

    public function addNote(string $type, int $id, string $note, int $userId): void
    {
        $this->type($type);
        $this->db->table('submission_notes')->insert([
            'submission_type' => $type,
            'submission_id'   => $id,
            'user_id'         => $userId,
            'note'            => $note,
            'created_at'      => Time::now('UTC')->toDateTimeString(),
        ]);

        // The note itself stays out of the audit log.
        $this->audit->record('submission.note-added', $userId, $type, $id);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function notes(string $type, int $id): array
    {
        return $this->db->table('submission_notes')
            ->select('submission_notes.*, users.username')
            ->join('users', 'users.id = submission_notes.user_id', 'left')
            ->where(['submission_type' => $type, 'submission_id' => $id])
            ->orderBy('submission_notes.id')
            ->get()
            ->getResultArray();
    }

    /**
     * Queues one email per recipient of each active rule for this type. The
     * message holds the type, the reference and a link to the staff area.
     */
    public function notify(string $type, int $id, string $reference): int
    {
        $definition = $this->type($type);
        $link       = site_url('admin/' . $definition['inbox'] . '/' . $type . '/' . $id);
        $queued     = 0;

        $rules = $this->db->table('notification_rules')
            ->where(['submission_type' => $type, 'is_active' => 1])
            ->get()
            ->getResultArray();

        foreach ($rules as $rule) {
            $subject = str_replace('{reference}', $reference, (string) $rule['subject']);
            $body    = trim((string) $rule['intro']) . "\n\n"
                . 'Type: ' . $definition['label'] . "\n"
                . 'Reference: ' . $reference . "\n\n"
                . 'Open it in the staff area (sign-in required): ' . $link . "\n";

            foreach (self::recipients((string) $rule['recipients']) as $to) {
                $this->mail->queue('notification', $to, $subject, ltrim($body));
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * Valid addresses from a rule's recipient list, one per line or comma.
     *
     * @return list<string>
     */
    public static function recipients(string $list): array
    {
        $addresses = array_filter(array_map('trim', preg_split('/[\s,;]+/', $list) ?: []));

        return array_values(array_unique(array_filter($addresses, static fn (string $a): bool => filter_var($a, FILTER_VALIDATE_EMAIL) !== false)));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function touch(string $type, int $id, array $values): void
    {
        $values['updated_at'] = Time::now('UTC')->toDateTimeString();
        $this->db->table($this->type($type)['table'])->where('id', $id)->update($values);
    }

    /**
     * @param array<string, mixed> $definition
     */
    private function newReference(array $definition): string
    {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= self::REFERENCE_ALPHABET[random_int(0, strlen(self::REFERENCE_ALPHABET) - 1)];
            }
            $reference = $definition['prefix'] . '-' . date('Y') . '-' . $code;
        } while ($this->db->table($definition['table'])->where('reference', $reference)->countAllResults() > 0);

        return $reference;
    }
}

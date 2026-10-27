<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\I18n\Time;

/**
 * Writes administration activity to the append-only audit_events table.
 *
 * Callers must never pass passwords, tokens or the content of personal
 * submissions in $details. Keys listed in REDACTED_KEYS are removed as a
 * safeguard.
 */
class AuditLog
{
    private const REDACTED_KEYS = ['password', 'password_confirm', 'token', 'secret', 'csrf_token'];

    public function __construct(
        private readonly ConnectionInterface $db,
        private readonly ?IncomingRequest $request = null,
    ) {
    }

    /**
     * @param array<string, mixed> $details
     */
    public function record(
        string $event,
        ?int $actorUserId = null,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $details = [],
    ): void {
        foreach (self::REDACTED_KEYS as $key) {
            unset($details[$key]);
        }

        $this->db->table('audit_events')->insert([
            'occurred_at'   => Time::now('UTC')->toDateTimeString(),
            'actor_user_id' => $actorUserId,
            'event'         => $event,
            'subject_type'  => $subjectType,
            'subject_id'    => $subjectId === null ? null : (string) $subjectId,
            'ip_address'    => $this->request?->getIPAddress(),
            'details'       => $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(int $limit = 50): array
    {
        return $this->db->table('audit_events')
            ->orderBy('id', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }
}

<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;

/**
 * Outgoing email is queued and sent by a scheduled command, so public forms
 * never wait on the mail server (PD-10). Mail goes through the SMTP relay set
 * in .env, which on the live site is MOFAIC's own relay (brief section 6
 * item 7 leaves the transport open).
 */
class MailQueue
{
    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly BaseConnection $db)
    {
    }

    public function queue(string $purpose, string $to, string $subject, string $body): int
    {
        $this->db->table('mail_queue')->insert([
            'purpose'    => $purpose,
            'to_email'   => $to,
            'subject'    => mb_substr($subject, 0, 255),
            'body'       => $body,
            'status'     => 'queued',
            'created_at' => Time::now('UTC')->toDateTimeString(),
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Sends up to $limit queued messages. Returns [sent, failed].
     *
     * @param callable(array<string, mixed>): ?string|null $transport Returns an error message or null. Defaults to the Email service.
     *
     * @return array{int, int}
     */
    public function send(int $limit = 50, ?callable $transport = null): array
    {
        $transport ??= $this->smtp(...);
        $sent      = 0;
        $failed    = 0;

        $rows = $this->db->table('mail_queue')
            ->where('status', 'queued')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $error    = $transport($row);
            $attempts = (int) $row['attempts'] + 1;

            if ($error === null) {
                $this->db->table('mail_queue')->where('id', $row['id'])->update([
                    'status' => 'sent', 'attempts' => $attempts, 'last_error' => null, 'sent_at' => Time::now('UTC')->toDateTimeString(),
                ]);
                $sent++;

                continue;
            }

            $this->db->table('mail_queue')->where('id', $row['id'])->update([
                'status'     => $attempts >= self::MAX_ATTEMPTS ? 'failed' : 'queued',
                'attempts'   => $attempts,
                'last_error' => mb_substr($error, 0, 500),
            ]);
            $failed++;
        }

        return [$sent, $failed];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function smtp(array $row): ?string
    {
        $email = service('email', null, false);
        $email->setTo((string) $row['to_email']);
        $email->setSubject((string) $row['subject']);
        $email->setMessage((string) $row['body']);
        $email->setMailType('text');

        if ($email->send(false)) {
            return null;
        }

        // The debugger output can contain the message, so keep only the first line.
        return strtok(strip_tags($email->printDebugger(['headers'])), "\n") ?: 'Sending failed.';
    }
}

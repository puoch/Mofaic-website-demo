<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\MailQueue;
use Tests\Support\StaffTestCase;

/**
 * Queued email (PD-10): forms never wait on the mail server, and failures
 * are retried a limited number of times.
 */
final class MailQueueTest extends StaffTestCase
{
    public function testSendsQueuedMessagesInOrder(): void
    {
        $queue = service('mailQueue');
        $queue->queue('test', 'one@example.invalid', 'One', 'Body');
        $queue->queue('test', 'two@example.invalid', 'Two', 'Body');

        $seen = [];
        [$sent, $failed] = $queue->send(10, static function (array $row) use (&$seen): ?string {
            $seen[] = $row['to_email'];

            return null;
        });

        $this->assertSame([2, 0], [$sent, $failed]);
        $this->assertSame(['one@example.invalid', 'two@example.invalid'], $seen);
        $this->assertSame(2, $this->db->table('mail_queue')->where('status', 'sent')->countAllResults());
    }

    public function testFailuresAreRetriedThenMarkedFailed(): void
    {
        $queue = service('mailQueue');
        $queue->queue('test', 'one@example.invalid', 'One', 'Body');
        $fail = static fn (array $row): string => 'Relay refused';

        for ($i = 1; $i < MailQueue::MAX_ATTEMPTS; $i++) {
            $queue->send(10, $fail);
            $this->assertSame('queued', $this->db->table('mail_queue')->get()->getRow()->status);
        }

        $queue->send(10, $fail);
        $row = $this->db->table('mail_queue')->get()->getRow();
        $this->assertSame('failed', $row->status);
        $this->assertSame('Relay refused', $row->last_error);
        $this->assertSame([0, 0], $queue->send(10, $fail), 'Failed messages are not tried again.');
    }
}

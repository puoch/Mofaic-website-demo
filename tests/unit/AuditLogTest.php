<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\StaffTestCase;

final class AuditLogTest extends StaffTestCase
{
    public function testRecordsAnEvent(): void
    {
        service('auditLog')->record('content.published', 7, 'article', 42, ['title' => 'Example']);

        $row = $this->db->table('audit_events')->get()->getRowArray();

        $this->assertSame('content.published', $row['event']);
        $this->assertSame('7', (string) $row['actor_user_id']);
        $this->assertSame('article', $row['subject_type']);
        $this->assertSame('42', $row['subject_id']);
        $this->assertSame(['title' => 'Example'], json_decode($row['details'], true));
    }

    public function testSecretsAreNeverStored(): void
    {
        service('auditLog')->record('auth.test', null, null, null, [
            'email'    => 'someone@example.invalid',
            'password' => 'secret-value',
            'token'    => 'secret-token',
        ]);

        $details = $this->db->table('audit_events')->get()->getRowArray()['details'];

        $this->assertStringNotContainsString('secret-value', $details);
        $this->assertStringNotContainsString('secret-token', $details);
    }

    public function testLatestReturnsNewestFirst(): void
    {
        service('auditLog')->record('first');
        service('auditLog')->record('second');

        $this->assertSame(['second', 'first'], array_column(service('auditLog')->latest(), 'event'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Database\Seeds\DemoSeeder;
use RuntimeException;
use Tests\Support\StaffTestCase;

/**
 * Demonstration data must be unmistakably labelled and must never load in
 * production (instruction 10, PD-16).
 */
final class DemoSeederTest extends StaffTestCase
{
    public function testRefusesToRunInProduction(): void
    {
        $this->expectException(RuntimeException::class);
        DemoSeeder::assertAllowed('production');
    }

    public function testRefusesUnknownEnvironments(): void
    {
        $this->expectException(RuntimeException::class);
        DemoSeeder::assertAllowed('staging');
    }

    public function testEveryDemoRecordIsLabelled(): void
    {
        $this->seed(DemoSeeder::class);

        $departments = $this->db->table('departments')->get()->getResultArray();
        $this->assertNotEmpty($departments);
        foreach ($departments as $department) {
            $this->assertSame('1', (string) $department['is_demo']);
            $this->assertStringContainsString(DemoSeeder::LABEL, $department['name']);
        }

        $emails = array_column($this->db->table('auth_identities')->where('type', 'email_password')->get()->getResultArray(), 'secret');
        $this->assertCount(4, $emails);
        foreach ($emails as $email) {
            $this->assertStringEndsWith('@example.invalid', $email);
        }
    }

    public function testRunningTwiceCreatesNoDuplicates(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(3, $this->db->table('departments')->countAllResults());
        $this->assertSame(4, $this->db->table('users')->countAllResults());
    }
}

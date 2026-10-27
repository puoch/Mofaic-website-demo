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

    public function testDemoCountriesMissionsAndArticlesAreLabelled(): void
    {
        $this->seed(DemoSeeder::class);

        $countries = $this->db->table('countries')->get()->getResultArray();
        $this->assertCount(3, $countries);
        foreach ($countries as $country) {
            $this->assertSame('1', (string) $country['is_demo']);
            $this->assertMatchesRegularExpression('/^X[A-Z]$/', $country['iso_code'], 'Demo countries use ISO user-assigned codes only.');
            $this->assertStringContainsString('(DEMO)', $country['name']);
        }

        $missions = $this->db->table('missions')->get()->getResultArray();
        $this->assertNotEmpty($missions);
        foreach ($missions as $mission) {
            $this->assertSame('1', (string) $mission['is_demo']);
            $this->assertStringContainsString(DemoSeeder::LABEL, $mission['name']);
            $this->assertStringEndsWith('@example.invalid', (string) $mission['email']);
            $this->assertStringStartsWith('+000', (string) $mission['phone']);
        }

        foreach ($this->db->table('articles')->get()->getResultArray() as $article) {
            $this->assertStringContainsString(DemoSeeder::LABEL, $article['title']);
        }
    }

    public function testRunningTwiceCreatesNoDuplicates(): void
    {
        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $this->assertSame(3, $this->db->table('departments')->countAllResults());
        $this->assertSame(4, $this->db->table('users')->countAllResults());
        $this->assertSame(3, $this->db->table('countries')->countAllResults());
        $this->assertSame(3, $this->db->table('missions')->countAllResults());
    }
}

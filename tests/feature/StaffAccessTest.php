<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;
use Tests\Support\StaffTestCase;

/**
 * Staff sign-in and administration access (GOV-01, SEC-01, SEC-04).
 */
final class StaffAccessTest extends StaffTestCase
{
    public function testPublicRegistrationIsSwitchedOff(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('register');
    }

    public function testMagicLinkSignInIsSwitchedOff(): void
    {
        $this->expectException(PageNotFoundException::class);
        $this->get('login/magic-link');
    }

    public function testSignInFormIsRejectedWithoutACsrfToken(): void
    {
        $this->expectException(SecurityException::class);
        $this->post('login', ['email' => 'someone@example.invalid', 'password' => 'anything-at-all']);
    }

    public function testAdministrationNeedsSignIn(): void
    {
        $this->get('admin')->assertRedirectTo(site_url('login'));
    }

    /**
     * @return list<array{string}>
     */
    public static function roles(): array
    {
        return [['superadmin'], ['ministryadmin'], ['editor'], ['readonly']];
    }

    /**
     * @dataProvider roles
     */
    public function testEveryStaffRoleCanOpenTheDashboard(string $group): void
    {
        $result = $this->actingAs($this->staff($group))->get('admin');

        $result->assertOK();
        $result->assertSee(config('AuthGroups')->groups[$group]['title']);
    }

    public function testReadOnlyStaffCannotOpenTheAuditLog(): void
    {
        $result = $this->actingAs($this->staff('readonly'))->get('admin/audit');

        $this->assertTrue($result->isRedirect());
        $this->assertNotSame(site_url('admin/audit'), $result->getRedirectUrl());
    }

    public function testMinistryAdministratorCanOpenTheAuditLog(): void
    {
        $this->actingAs($this->staff('ministryadmin'))->get('admin/audit')->assertOK();
    }

    public function testFailedSignInIsAuditedWithoutThePassword(): void
    {
        $this->staff('editor', 'editor@example.invalid');

        $this->post('login', [
            csrf_token() => csrf_hash(),
            'email'    => 'editor@example.invalid',
            'password' => 'wrong-password-123',
        ]);

        $row = $this->db->table('audit_events')->where('event', 'auth.login_failed')->get()->getRowArray();

        $this->assertNotNull($row);
        $this->assertStringContainsString('editor@example.invalid', $row['details']);
        $this->assertStringNotContainsString('wrong-password-123', $row['details']);
    }

    public function testCorrectPasswordLeadsToTheEmailCodeStep(): void
    {
        $this->staff('editor', 'editor2@example.invalid');

        $result = $this->post('login', [
            csrf_token() => csrf_hash(),
            'email'    => 'editor2@example.invalid',
            'password' => 'correct-horse-battery-staple',
        ]);

        $result->assertRedirectTo(site_url('auth/a/show'));
        $this->assertFalse(auth()->loggedIn(), 'Sign-in must not complete before the emailed code is entered.');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\StructureSeeder;
use App\Libraries\MailQueue;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Models\UserModel;
use Tests\Support\StaffTestCase;

/**
 * Staff sign-in help (2026-11-10): coat of arms, "Forgot password",
 * "Request an account" with Super Administrator approval, and "Remember me"
 * limited to 15 days.
 */
final class SignInHelpTest extends StaffTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StructureSeeder::class);
    }

    public function testSignInPageShowsTheCoatOfArmsAndTheHelpLinks(): void
    {
        $page = $this->get('login');

        $page->assertOK();
        $this->assertStringContainsString('assets/brand/coat-of-arms.svg', $page->getBody());
        $this->assertStringContainsString('alt="Coat of arms of the Republic of South Sudan"', html_entity_decode($page->getBody()));
        $page->assertSee('data-testid="forgot-password-link"', null);
        $page->assertSee('data-testid="request-account-link"', null);
        $page->assertSee('up to 15 days');
        $this->assertStringContainsString('href="' . site_url('en') . '" data-testid="back-to-website"', html_entity_decode($page->getBody()));
    }

    public function testRememberMeLastsFifteenDays(): void
    {
        $this->assertSame(15 * DAY, config('Auth')->sessionConfig['rememberLength']);
    }

    public function testForgotPasswordGivesTheSameReplyForUnknownAddresses(): void
    {
        $this->staff('editor', 'editor@example.invalid');

        $unknown = $this->post('login/forgot-password', [csrf_token() => csrf_hash(), 'email' => 'nobody@example.invalid']);
        $known   = $this->post('login/forgot-password', [csrf_token() => csrf_hash(), 'email' => 'editor@example.invalid']);

        $unknown->assertRedirectTo(site_url('login/forgot-password'));
        $known->assertRedirectTo(site_url('login/forgot-password'));
        $this->assertSame(1, $this->db->table('mail_queue')->where('purpose', 'password-reset')->countAllResults());
        $this->assertSame('editor@example.invalid', $this->db->table('mail_queue')->get()->getRow()->to_email);
    }

    public function testBannedAccountsGetNoResetLink(): void
    {
        $user = $this->staff('editor', 'gone@example.invalid');
        $user->ban('Left the Ministry');

        $this->post('login/forgot-password', [csrf_token() => csrf_hash(), 'email' => 'gone@example.invalid']);

        $this->assertSame(0, $this->db->table('mail_queue')->countAllResults());
    }

    public function testResetLinkSetsANewPasswordOnceAndSignsOutRememberedDevices(): void
    {
        $user = $this->staff('editor', 'editor@example.invalid');
        $this->db->table('auth_remember_tokens')->insert([
            'selector' => 'abc', 'hashedValidator' => str_repeat('a', 64), 'user_id' => $user->id,
            'expires'  => Time::now()->addDays(5)->toDateTimeString(), 'created_at' => Time::now()->toDateTimeString(), 'updated_at' => Time::now()->toDateTimeString(),
        ]);

        $this->post('login/forgot-password', [csrf_token() => csrf_hash(), 'email' => 'editor@example.invalid']);
        $token = $this->tokenFromMail('password-reset');

        $this->assertSame(0, $this->db->table('staff_password_tokens')->where('token_hash', $token)->countAllResults(), 'Only a hash is stored.');

        $this->get('login/reset-password/' . $token)->assertSee('data-testid="reset-form"', null);

        $mismatch = $this->post('login/reset-password', [csrf_token() => csrf_hash(), 'token' => $token, 'password' => 'Tall-river-Juba-2026', 'password_confirm' => 'something-else']);
        $mismatch->assertSee('do not match');

        $weak = $this->post('login/reset-password', [csrf_token() => csrf_hash(), 'token' => $token, 'password' => 'short', 'password_confirm' => 'short']);
        $weak->assertSee('data-testid="reset-form"', null);

        $saved = $this->post('login/reset-password', [csrf_token() => csrf_hash(), 'token' => $token, 'password' => 'Tall-river-Juba-2026', 'password_confirm' => 'Tall-river-Juba-2026']);
        $saved->assertRedirectTo(site_url('login'));

        $user = model(UserModel::class)->findById($user->id);
        $this->assertTrue(service('passwords')->verify('Tall-river-Juba-2026', $user->getEmailIdentity()->secret2));
        $this->assertSame(0, $this->db->table('auth_remember_tokens')->where('user_id', $user->id)->countAllResults());
        $this->assertSame(1, $this->db->table('mail_queue')->where('purpose', 'password-changed')->countAllResults());

        $this->get('login/reset-password/' . $token)->assertSee('data-testid="link-invalid"', null);
    }

    public function testExpiredResetLinksDoNotWork(): void
    {
        $this->staff('editor', 'editor@example.invalid');
        $this->post('login/forgot-password', [csrf_token() => csrf_hash(), 'email' => 'editor@example.invalid']);
        $token = $this->tokenFromMail('password-reset');

        $this->db->table('staff_password_tokens')->update(['expires_at' => Time::now('UTC')->subMinutes(1)->toDateTimeString()]);

        $this->get('login/reset-password/' . $token)->assertSee('data-testid="link-invalid"', null);
        $this->post('login/reset-password', [csrf_token() => csrf_hash(), 'token' => $token, 'password' => 'Tall-river-Juba-2026', 'password_confirm' => 'Tall-river-Juba-2026'])
            ->assertSee('data-testid="link-invalid"', null);
    }

    public function testResetRequestsAreRateLimited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('login/forgot-password', [csrf_token() => csrf_hash(), 'email' => 'nobody' . $i . '@example.invalid']);
        }

        $this->post('login/forgot-password', [csrf_token() => csrf_hash(), 'email' => 'nobody@example.invalid'])->assertSessionHas('error');
    }

    public function testRequestFormStaysClosedUntilThePrivacyNoticeIsPublished(): void
    {
        $this->get('login/request-account')->assertSee('data-testid="form-closed"', null);
        $this->submitForm('login/request-account', 'account-request', $this->request());
        $this->assertSame(0, $this->db->table('staff_account_requests')->countAllResults());
    }

    public function testRequestIsStoredAsPendingAndSuperAdministratorsAreTold(): void
    {
        $this->openForms();
        $this->staff('superadmin', 'boss@example.invalid');

        $this->get('login/request-account')->assertSee('data-testid="privacy-notice"', null);
        $this->submitForm('login/request-account', 'account-request', $this->request())->assertRedirectTo(site_url('login/request-account'));
        $this->submitForm('login/request-account', 'account-request', $this->request());

        $this->assertSame(1, $this->db->table('staff_account_requests')->where('status', 'pending')->countAllResults(), 'A second request for the same address is not stored.');
        $this->assertSame(1, $this->db->table('mail_queue')->where(['purpose' => 'account-request', 'to_email' => 'boss@example.invalid'])->countAllResults());
        $this->assertNull((new UserModel())->findByCredentials(['email' => 'new.officer@example.invalid']), 'A request creates no account.');
    }

    public function testRequestFieldsAreRequired(): void
    {
        $this->openForms();

        $this->submitForm('login/request-account', 'account-request', ['email' => 'not-an-email'])->assertSee('aria-invalid="true"', null);
        $this->assertSame(0, $this->db->table('staff_account_requests')->countAllResults());
    }

    public function testSuperAdministratorApprovesWithARoleAndThePersonSetsTheirPassword(): void
    {
        $this->openForms();
        $admin = $this->staff('superadmin');
        $this->submitForm('login/request-account', 'account-request', $this->request());
        $id = (int) $this->db->table('staff_account_requests')->get()->getRow()->id;

        $this->actingAs($admin)->get('admin/staff')->assertSee('data-testid="request-' . $id . '"', null);

        $this->postAs($admin, 'admin/staff/requests/' . $id . '/approve', ['username' => 'new.officer', 'group' => 'editor'])->assertSessionHas('message');

        $user = (new UserModel())->findByCredentials(['email' => 'new.officer@example.invalid']);
        $this->assertNotNull($user);
        $this->assertTrue($user->inGroup('editor'));
        $this->assertSame('approved', $this->db->table('staff_account_requests')->get()->getRow()->status);

        $token = $this->tokenFromMail('account-approved');
        $_SESSION = [];
        $this->resetServices(true);
        $this->post('login/reset-password', [csrf_token() => csrf_hash(), 'token' => $token, 'password' => 'Tall-river-Juba-2026', 'password_confirm' => 'Tall-river-Juba-2026'])
            ->assertRedirectTo(site_url('login'));
    }

    public function testDecliningCreatesNoAccount(): void
    {
        $this->openForms();
        $admin = $this->staff('superadmin');
        $this->submitForm('login/request-account', 'account-request', $this->request());
        $id = (int) $this->db->table('staff_account_requests')->get()->getRow()->id;

        $this->postAs($admin, 'admin/staff/requests/' . $id . '/decline')->assertSessionHas('message');

        $this->assertSame('declined', $this->db->table('staff_account_requests')->get()->getRow()->status);
        $this->assertNull((new UserModel())->findByCredentials(['email' => 'new.officer@example.invalid']));
        $this->assertSame(1, $this->db->table('mail_queue')->where('purpose', 'account-declined')->countAllResults());
    }

    public function testOnlySuperAdministratorsCanApprove(): void
    {
        $this->openForms();
        $this->submitForm('login/request-account', 'account-request', $this->request());
        $id = (int) $this->db->table('staff_account_requests')->get()->getRow()->id;

        $this->postAs($this->staff('ministryadmin'), 'admin/staff/requests/' . $id . '/approve', ['username' => 'new.officer', 'group' => 'superadmin']);

        $this->assertSame('pending', $this->db->table('staff_account_requests')->get()->getRow()->status);
        $this->assertNull((new UserModel())->findByCredentials(['email' => 'new.officer@example.invalid']));
    }

    public function testSentLinkEmailsAreNotKeptInTheQueue(): void
    {
        $this->staff('editor', 'editor@example.invalid');
        $this->post('login/forgot-password', [csrf_token() => csrf_hash(), 'email' => 'editor@example.invalid']);

        service('mailQueue')->send(10, static fn (): ?string => null);

        $body = (string) $this->db->table('mail_queue')->get()->getRow()->body;
        $this->assertStringNotContainsString('reset-password', $body);
        $this->assertContains('password-reset', MailQueue::SECRET_PURPOSES);
    }

    /**
     * @return array<string, string>
     */
    private function request(): array
    {
        return [
            'full_name' => 'New Officer',
            'email'     => 'New.Officer@example.invalid',
            'office'    => 'Test directorate',
            'job_title' => 'Communications officer',
            'reason'    => 'Publish news for the directorate.',
        ];
    }

    private function tokenFromMail(string $purpose): string
    {
        $body = (string) $this->db->table('mail_queue')->where('purpose', $purpose)->get()->getRow()->body;
        $this->assertSame(1, preg_match('#login/reset-password/([0-9a-f]{64})#', $body, $m));

        return $m[1];
    }
}

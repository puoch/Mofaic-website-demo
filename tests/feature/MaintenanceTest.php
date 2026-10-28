<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Config\Factories;
use CodeIgniter\Shield\Models\UserModel;
use Config\Hosting;
use Tests\Support\StaffTestCase;

/**
 * /maintenance, the setup page for hosts without SSH (docs/infinityfree.md).
 */
final class MaintenanceTest extends StaffTestCase
{
    private const TOKEN = 'test-token-0123456789abcdef0123456789abcdef';

    private function enable(string $token = self::TOKEN): void
    {
        $config                   = new Hosting();
        $config->maintenanceToken = $token;
        Factories::injectMock('config', 'Hosting', $config);
    }

    /**
     * @param array<string, string> $data
     */
    private function send(array $data): \CodeIgniter\Test\TestResponse
    {
        return $this->post('maintenance', $data + [csrf_token() => csrf_hash()]);
    }

    public function testSwitchedOffByDefault(): void
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->get('maintenance');
    }

    public function testSwitchedOffWithAShortToken(): void
    {
        $this->enable('too-short');

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->get('maintenance');
    }

    public function testAsksForTheTokenAndRefusesAWrongOne(): void
    {
        $this->enable();

        $page = $this->get('maintenance');
        $page->assertOK();
        $page->assertSee('Maintenance token');

        $result = $this->send(['token' => 'wrong', 'action' => 'migrate']);
        $result->assertSee('That is not the maintenance token. You entered 5 characters; the token in .env has ' . strlen(self::TOKEN) . '.');
        $result->assertDontSee(self::TOKEN);
        $result->assertDontSee('Create a staff account');
        $this->assertSame(1, $this->db->table('audit_events')->where('event', 'maintenance.denied')->countAllResults());
    }

    public function testTheTokenStaysOutOfErrorLogs(): void
    {
        $parameter = new \ReflectionParameter([\App\Controllers\Maintenance::class, 'page'], 'token');

        $this->assertNotSame([], $parameter->getAttributes(\SensitiveParameter::class));
    }

    public function testAcceptsAPastedTokenWithSpaceAround(): void
    {
        $this->enable();

        $result = $this->send(['token' => ' ' . self::TOKEN . "\r\n", 'action' => 'migrate']);

        $result->assertSee('Migrations and structure pages are up to date.');
    }

    public function testRunsMigrationsAndShowsTheGoLiveCheck(): void
    {
        $this->enable();

        $result = $this->send(['token' => self::TOKEN, 'action' => 'migrate']);

        $result->assertOK();
        $result->assertSee('Migrations and structure pages are up to date.');
        $result->assertSee('All migrations have run.');
        $result->assertSee('Maintenance page is switched off');
        $this->assertSame('no-store', $result->response()->getHeaderLine('Cache-Control'));
    }

    public function testLoadsTheEmbassiesAsDraftsOnce(): void
    {
        $this->enable();

        $this->send(['token' => self::TOKEN, 'action' => 'load_missions'])->assertSee('Added 21 mission draft(s).');
        $this->send(['token' => self::TOKEN, 'action' => 'load_missions'])->assertSee('Added 0 mission draft(s).');
        $this->assertSame(0, $this->db->table('missions')->where('status', 'published')->countAllResults());
    }

    public function testCreatesAStaffAccountWithItsRole(): void
    {
        $this->enable();

        $result = $this->send(['token' => self::TOKEN, 'action' => 'create_user', 'username' => 'firstadmin', 'email' => 'first.admin@example.invalid', 'password' => 'correct horse battery staple 42', 'group' => 'superadmin']);

        $result->assertSee('Created first.admin@example.invalid as superadmin.');
        $user = model(UserModel::class)->findByCredentials(['email' => 'first.admin@example.invalid']);
        $this->assertNotNull($user);
        $this->assertTrue($user->inGroup('superadmin'));
        $this->assertTrue($user->isActivated());
        $this->assertSame(1, $this->db->table('audit_events')->where('event', 'staff.created')->countAllResults());
    }

    public function testRefusesWeakPasswordsAndUnknownRoles(): void
    {
        $this->enable();

        $this->send(['token' => self::TOKEN, 'action' => 'create_user', 'username' => 'weak', 'email' => 'weak@example.invalid', 'password' => 'short', 'group' => 'superadmin'])->assertSee('Not created:');
        $this->send(['token' => self::TOKEN, 'action' => 'create_user', 'username' => 'role', 'email' => 'role@example.invalid', 'password' => 'correct horse battery staple 42', 'group' => 'owner'])->assertSee('Not created:');

        $this->assertNull(model(UserModel::class)->findByCredentials(['email' => 'weak@example.invalid']));
        $this->assertNull(model(UserModel::class)->findByCredentials(['email' => 'role@example.invalid']));
    }

    public function testLimitsAttemptsPerAddress(): void
    {
        $this->enable();

        for ($i = 0; $i < 10; $i++) {
            $this->send(['token' => 'wrong']);
        }

        $this->send(['token' => self::TOKEN])->assertStatus(429);
    }
}

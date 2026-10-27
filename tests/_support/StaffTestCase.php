<?php

declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Base test case with a migrated in-memory database (all namespaces,
 * including Shield) and helpers to create staff accounts by role.
 */
abstract class StaffTestCase extends CIUnitTestCase
{
    use AuthenticationTesting;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    protected function setUp(): void
    {
        parent::setUp();

        // actingAs() signs in through the shared auth service and session;
        // start every test signed out so results do not depend on run order.
        $_SESSION = [];
        $this->resetServices(true);
    }

    protected function staff(string $group, string $email = ''): User
    {
        /** @var User $user */
        $user = fake(UserModel::class, [
            'username' => 'test-' . $group . '-' . bin2hex(random_bytes(3)),
            'active'   => 1,
        ]);
        $user->createEmailIdentity([
            'email'    => $email !== '' ? $email : $user->username . '@example.invalid',
            'password' => 'correct-horse-battery-staple',
        ]);
        $user->addGroup($group);

        return $user;
    }

    /**
     * Signs in as $user and posts a form with a valid CSRF token.
     *
     * @param array<string, mixed> $data
     */
    protected function postAs(User $user, string $uri, array $data = []): \CodeIgniter\Test\TestResponse
    {
        return $this->actingAs($user)->post($uri, [csrf_token() => csrf_hash()] + $data);
    }

    /**
     * Creates a record through the content service, optionally published.
     *
     * @param array<string, mixed> $values
     */
    protected function content(string $type, array $values, bool $publish = true): int
    {
        $id = service('content')->save($type, null, $values, null);

        if ($publish) {
            service('content')->publish($type, $id, null);
        }

        return $id;
    }

    protected function country(string $iso, string $name): int
    {
        $this->db->table('countries')->insert(['iso_code' => $iso, 'name' => $name, 'is_demo' => 1]);

        return (int) $this->db->insertID();
    }

    protected function department(string $slug, bool $diaspora = false): int
    {
        $this->db->table('departments')->insert([
            'slug'        => $slug,
            'name'        => 'Test department ' . $slug,
            'is_diaspora' => $diaspora ? 1 : 0,
            'is_demo'     => 1,
        ]);

        return (int) $this->db->insertID();
    }
}

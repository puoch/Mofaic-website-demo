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

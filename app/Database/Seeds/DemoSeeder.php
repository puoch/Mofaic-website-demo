<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use RuntimeException;

/**
 * DEMONSTRATION DATA ONLY. NOT OFFICIAL MINISTRY DATA.
 *
 * Creates fictional departments and one staff account per role so the
 * administration area can be tried locally. Every record is labelled DEMO and
 * uses the reserved example.invalid domain. It refuses to run in production
 * (your instruction 10, decision PD-16).
 *
 *   php spark db:seed DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public const LABEL = 'DEMO, NOT OFFICIAL';

    /**
     * @var list<string>
     */
    public const ALLOWED_ENVIRONMENTS = ['development', 'testing'];

    public static function assertAllowed(string $environment): void
    {
        if (! in_array($environment, self::ALLOWED_ENVIRONMENTS, true)) {
            throw new RuntimeException('Demo data may only be loaded in development or testing, not in "' . $environment . '".');
        }
    }

    public function run(): void
    {
        self::assertAllowed(ENVIRONMENT);

        $departments = $this->db->table('departments');
        $now         = date('Y-m-d H:i:s');

        $demoDepartments = [
            ['slug' => 'demo-department-a', 'name' => 'Demo Department A (' . self::LABEL . ')', 'is_diaspora' => 0],
            ['slug' => 'demo-department-b', 'name' => 'Demo Department B (' . self::LABEL . ')', 'is_diaspora' => 0],
            ['slug' => 'demo-diaspora', 'name' => 'Demo Diaspora Department (' . self::LABEL . ')', 'is_diaspora' => 1],
        ];

        foreach ($demoDepartments as $row) {
            if ($departments->where('slug', $row['slug'])->countAllResults() === 0) {
                $departments->insert($row + ['is_demo' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $ids = array_column(
            $this->db->table('departments')->where('is_demo', 1)->get()->getResultArray(),
            'id',
            'slug',
        );

        $password = getenv('DEMO_PASSWORD') ?: bin2hex(random_bytes(8));

        $accounts = [
            'superadmin'    => [],
            'ministryadmin' => [],
            'editor'        => ['demo-department-a', 'demo-diaspora'],
            'readonly'      => [],
        ];

        $users = auth()->getProvider();

        foreach ($accounts as $group => $memberOf) {
            $email = 'demo-' . $group . '@example.invalid';

            if ($users->findByCredentials(['email' => $email]) !== null) {
                continue;
            }

            $user = new User([
                'username' => 'demo-' . $group,
                'email'    => $email,
                'password' => $password,
            ]);
            $users->save($user);

            $user = $users->findById($users->getInsertID());
            $user->addGroup($group);

            foreach ($memberOf as $slug) {
                service('departmentAccess')->assign($user, (int) $ids[$slug]);
            }
        }

        if (is_cli() && ENVIRONMENT !== 'testing') {
            CLI::write('Demo accounts: demo-<role>@example.invalid (roles: ' . implode(', ', array_keys($accounts)) . ')', 'yellow');
            CLI::write(getenv('DEMO_PASSWORD') ? 'Password: the value of DEMO_PASSWORD.' : 'Password for all demo accounts: ' . $password, 'yellow');
        }
    }
}

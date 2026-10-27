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

        $this->demoContent($users->findByCredentials(['email' => 'demo-ministryadmin@example.invalid']));

        if (is_cli() && ENVIRONMENT !== 'testing') {
            CLI::write('Demo accounts: demo-<role>@example.invalid (roles: ' . implode(', ', array_keys($accounts)) . ')', 'yellow');
            CLI::write(getenv('DEMO_PASSWORD') ? 'Password: the value of DEMO_PASSWORD.' : 'Password for all demo accounts: ' . $password, 'yellow');
        }
    }

    /**
     * Fictional countries, missions and articles so the public pages can be
     * tried. Countries use ISO 3166 user-assigned codes (XA, XB, XC), which no
     * real country has. Telephone numbers are not real.
     */
    private function demoContent(?User $admin): void
    {
        if ($this->db->table('countries')->where('iso_code', 'XA')->countAllResults() > 0) {
            return;
        }

        $content = service('content');
        $adminId = $admin === null ? null : (int) $admin->id;
        $now     = date('Y-m-d H:i:s');

        $countries = [];
        foreach (['XA' => 'Demoland', 'XB' => 'Samplestan', 'XC' => 'Exampleia'] as $iso => $name) {
            $this->db->table('countries')->insert(['iso_code' => $iso, 'name' => $name . ' (DEMO)', 'is_demo' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $countries[$iso] = (int) $this->db->insertID();
        }

        $mission = static fn (string $name, string $category, ?int $location, ?int $represents = null): array => [
            'name'                  => $name . ' (' . self::LABEL . ')',
            'category'              => $category,
            'location_country_id'   => $location,
            'represents_country_id' => $represents,
            'city'                  => 'Demo City',
            'address'               => "1 Example Street\nDemo City",
            'phone'                 => '+000 0000 0000',
            'email'                 => 'mission@example.invalid',
            'head_of_mission_title' => 'Ambassador (demo)',
            'head_of_mission_name'  => 'Demo Person',
            'body'                  => 'This is a fictional mission record for testing. ' . self::LABEL . '.',
        ];

        $embassy = $content->save('missions', null, $mission('Demo Embassy in Demoland', 'abroad_embassy', $countries['XA']), $adminId);
        $consulate = $content->save('missions', null, $mission('Demo Consulate in Samplestan', 'abroad_consulate', $countries['XB']), $adminId);
        $foreign = $content->save('missions', null, $mission('Demo Embassy of Demoland', 'foreign_embassy', null, $countries['XA']), $adminId);

        foreach ([[$embassy, 'XA', 'resident'], [$embassy, 'XC', 'non_resident'], [$consulate, 'XB', 'resident']] as [$missionId, $iso, $coverage]) {
            $this->db->table('mission_coverage')->insert(['mission_id' => $missionId, 'country_id' => $countries[$iso], 'coverage' => $coverage]);
        }

        // The editing form has no is_demo field, so mark the rows directly.
        $this->db->table('missions')->whereIn('id', [$embassy, $consulate, $foreign])->update(['is_demo' => 1]);

        foreach ([$embassy, $consulate, $foreign] as $id) {
            $content->publish('missions', $id, $adminId);
        }

        $articles = [
            ['category' => 'news', 'title' => 'Demo news article', 'is_featured' => 1, 'tags' => ''],
            ['category' => 'news', 'title' => 'Demo engagement article', 'is_featured' => 0, 'tags' => "Minister's engagements"],
            ['category' => 'statement', 'title' => 'Demo statement', 'is_featured' => 0, 'tags' => ''],
            ['category' => 'mission_activity', 'title' => 'Demo mission activity', 'is_featured' => 0, 'tags' => '', 'mission_id' => $embassy],
        ];

        foreach ($articles as $article) {
            $article['title'] .= ' (' . self::LABEL . ')';
            $article['summary'] = 'Fictional text for testing.';
            $article['body']    = "Fictional text for testing.\n\n" . self::LABEL . '.';
            $id                 = $content->save('articles', null, $article, $adminId);
            $content->publish('articles', $id, $adminId);
        }
    }
}

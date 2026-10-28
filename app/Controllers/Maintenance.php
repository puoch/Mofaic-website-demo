<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Seeds\MissionsAbroadSeeder;
use App\Database\Seeds\StructureSeeder;
use App\Libraries\GoLiveCheck;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Models\UserModel;
use Config\Database;
use Config\GoLive;
use Config\Hosting;
use Throwable;

/**
 * /maintenance: database migrations, structure pages, the embassies list,
 * staff accounts and the go-live check for hosts without SSH (Config\Hosting,
 * docs/infinityfree.md).
 *
 * Switched off (404) unless hosting.maintenanceToken is set in .env. Every
 * action needs the token, attempts are limited per address, and every
 * action is written to the audit log.
 */
class Maintenance extends BaseController
{
    private const ATTEMPTS_PER_HOUR = 10;

    public function index(): string
    {
        $hosting = config(Hosting::class);

        if (! $hosting->maintenanceEnabled()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $this->response->setHeader('Cache-Control', 'no-store')->setHeader('X-Robots-Tag', 'noindex, nofollow');

        if ($this->request->getMethod() !== 'POST') {
            return $this->page();
        }

        $key = 'maintenance-' . md5((string) $this->request->getIPAddress());

        if (! service('throttler')->check($key, self::ATTEMPTS_PER_HOUR, HOUR)) {
            $this->response->setStatusCode(429);

            return $this->page(error: 'Too many attempts. Try again in an hour.');
        }

        // Pasting often brings a space or line break along.
        $token = trim((string) $this->request->getPost('token'));

        if (! hash_equals($hosting->maintenanceToken, $token)) {
            $this->audit('maintenance.denied');

            // Lengths only: enough to spot a pasted placeholder such as <...>
            // or a cut-off copy, without revealing any of the token.
            return $this->page(error: sprintf(
                'That is not the maintenance token. You entered %d characters; the token in .env has %d.',
                strlen($token),
                strlen($hosting->maintenanceToken),
            ));
        }

        $message = match ((string) $this->request->getPost('action')) {
            'migrate'       => $this->migrate(),
            'create_user'   => $this->createUser(),
            'load_missions' => $this->loadMissions(),
            default         => null,
        };

        return $this->page(token: $token, message: $message, unlocked: true);
    }

    private function migrate(): string
    {
        service('migrations')->setNamespace(null)->latest();
        Database::seeder()->setSilent(true)->call(StructureSeeder::class);
        $this->audit('maintenance.migrated');

        return 'Migrations and structure pages are up to date.';
    }

    private function loadMissions(): string
    {
        $before = $this->missionCount();
        Database::seeder()->setSilent(true)->call(MissionsAbroadSeeder::class);
        $added = $this->missionCount() - $before;
        $this->audit('maintenance.missions_loaded', null, null, ['added' => $added]);

        return "Added {$added} mission draft(s). Check and publish them under Content, Missions.";
    }

    private function missionCount(): int
    {
        return Database::connect()->table('missions')->countAllResults();
    }

    private function createUser(): string
    {
        $result = service('staffAccounts')->create($this->request->getPost(['username', 'email', 'password', 'group']), null, ['via' => 'maintenance page']);

        if (is_array($result)) {
            return 'Not created: ' . implode(' ', $result);
        }

        return "Created {$result->email} as {$this->request->getPost('group')}.";
    }

    /**
     * @param array<string, mixed> $details
     */
    private function audit(string $event, ?string $subjectType = null, int|string|null $subjectId = null, array $details = []): void
    {
        try {
            service('auditLog')->record($event, null, $subjectType, $subjectId, ['via' => 'maintenance page'] + $details);
        } catch (Throwable) {
            // Before the first migration there is no audit table yet.
        }
    }

    /**
     * The token is marked sensitive so an error log's stack trace shows it as
     * an object, not in plain text.
     */
    private function page(#[\SensitiveParameter] string $token = '', ?string $message = null, ?string $error = null, bool $unlocked = false): string
    {
        $data = ['token' => $token, 'message' => $message, 'error' => $error, 'unlocked' => $unlocked];

        if ($unlocked) {
            $runner = service('migrations')->setNamespace(null);

            $data['pending']  = max(0, count($runner->findMigrations()) - count($runner->getHistory(config('Database')->defaultGroup)));
            $data['groups']   = config('AuthGroups')->groups;
            $data['staff']    = $data['pending'] === 0 ? model(UserModel::class)->countAllResults() : 0;
            $data['checks']   = $data['pending'] === 0 ? (new GoLiveCheck(new GoLive()))->run() : [];
        }

        return view('maintenance', $data);
    }
}

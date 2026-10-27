<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database\Seeds\StructureSeeder;
use App\Libraries\GoLiveCheck;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Authentication\Passwords\ValidationRules as PasswordRules;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Validation\ValidationRules;
use Config\Database;
use Config\GoLive;
use Config\Hosting;
use Throwable;

/**
 * /maintenance: database migrations, structure pages, staff accounts and the
 * go-live check for hosts without SSH (Config\Hosting, docs/infinityfree.md).
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

        $token = (string) $this->request->getPost('token');

        if (! hash_equals($hosting->maintenanceToken, $token)) {
            $this->audit('maintenance.denied');

            return $this->page(error: 'That is not the maintenance token.');
        }

        $message = match ((string) $this->request->getPost('action')) {
            'migrate'     => $this->migrate(),
            'create_user' => $this->createUser(),
            default       => null,
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

    private function createUser(): string
    {
        $groups = array_keys(config('AuthGroups')->groups);
        $rules  = (new ValidationRules())->getRegistrationRules();
        unset($rules['password_confirm']);
        $rules['group'] = ['label' => 'Role', 'rules' => ['required', 'in_list[' . implode(',', $groups) . ']']];

        // Shield's strong_password rule is not loaded by default, because
        // public registration is switched off.
        config('Validation')->ruleSets[] = PasswordRules::class;
        $validation = service('validation', null, false);

        if (! $validation->setRules($rules)->run($this->request->getPost(['username', 'email', 'password', 'group']))) {
            return 'Not created: ' . implode(' ', $validation->getErrors());
        }

        $users = auth()->getProvider();
        $user  = new User([
            'username' => (string) $this->request->getPost('username'),
            'email'    => (string) $this->request->getPost('email'),
            'password' => (string) $this->request->getPost('password'),
        ]);
        $users->save($user);

        /** @var User $user */
        $user = $users->findById($users->getInsertID());
        $user->activate();
        $user->addGroup((string) $this->request->getPost('group'));

        $this->audit('staff.created', 'user', $user->id, ['group' => $this->request->getPost('group')]);

        return "Created {$user->email} as {$this->request->getPost('group')}.";
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

    private function page(string $token = '', ?string $message = null, ?string $error = null, bool $unlocked = false): string
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

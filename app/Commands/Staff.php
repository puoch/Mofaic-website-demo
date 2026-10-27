<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Staff access tasks for the quarterly access review and offboarding
 * (docs/operations.md). The same tasks, plus creating accounts and changing
 * roles, are on the staff screen (/admin/staff, Controllers\Admin\Staff).
 *
 *   php spark staff list
 *   php spark staff ban <email> [--reason "Left the Ministry"]
 *   php spark staff unban <email>
 *   php spark staff department <email> <department-slug> [--remove]
 *
 * Every change is written to the audit log.
 */
class Staff extends BaseCommand
{
    protected $group       = 'MOFAIC';
    protected $name        = 'staff';
    protected $description = 'Lists staff access for the access review; bans or unbans accounts; sets department membership.';
    protected $usage       = 'staff list | ban <email> [--reason ""] | unban <email> | department <email> <department-slug> [--remove]';
    protected $options     = [
        '--reason' => 'With ban: the reason, shown to the person if they try to sign in.',
        '--remove' => 'With department: remove instead of add.',
    ];

    /** @var array{reason: string, remove: bool} */
    private array $chosen = ['reason' => '', 'remove' => false];

    public function run(array $params): int
    {
        // Options arrive in $params when the command is called from code, and
        // through CLI::getOption() from a terminal.
        $this->chosen = [
            'reason' => (string) ($params['reason'] ?? CLI::getOption('reason') ?? ''),
            'remove' => array_key_exists('remove', $params) || CLI::getOption('remove') !== null,
        ];
        $args = array_values(array_filter($params, 'is_int', ARRAY_FILTER_USE_KEY));

        return match ($args[0] ?? 'list') {
            'list'       => $this->list(),
            'ban'        => $this->ban($args[1] ?? '', true),
            'unban'      => $this->ban($args[1] ?? '', false),
            'department' => $this->department($args[1] ?? '', $args[2] ?? ''),
            default      => $this->usage(),
        };
    }

    private function usage(): int
    {
        CLI::error('Usage: php spark ' . $this->usage);

        return EXIT_USER_INPUT;
    }

    private function find(string $email): ?User
    {
        $user = $email === '' ? null : model(UserModel::class)->findByCredentials(['email' => $email]);

        if ($user === null) {
            CLI::error("No staff account with the email address \"{$email}\".");
        }

        return $user;
    }

    /**
     * Who ran the command, for the audit log (there is no signed-in user).
     *
     * @return array<string, string>
     */
    private function operator(): array
    {
        return ['via' => 'command line', 'operator' => (string) (getenv('SUDO_USER') ?: get_current_user())];
    }

    private function list(): int
    {
        $db          = db_connect();
        $departments = array_column($db->table('departments')->select('id, slug')->get()->getResultArray(), 'slug', 'id');
        $diaspora    = service('diasporaAccess')->holders();
        $rows        = [];

        foreach (model(UserModel::class)->withIdentities()->withGroups()->findAll() as $user) {
            $memberOf = array_map(static fn (array $r): string => $departments[(int) $r['department_id']] ?? '?', $db->table('department_user')->where('user_id', $user->id)->get()->getResultArray());
            $lastSignIn = $db->table('auth_logins')->selectMax('date')->where('user_id', $user->id)->where('success', 1)->get()->getRow()?->date;

            $rows[] = [
                $user->email ?? '',
                implode(', ', $user->getGroups()),
                implode(', ', $memberOf),
                $diaspora[(int) $user->id] ?? '',
                $user->isBanned() ? 'banned' : 'can sign in',
                $lastSignIn ?? 'never',
            ];
        }

        CLI::table($rows, ['Email', 'Roles', 'Departments', 'Diaspora access', 'Status', 'Last sign-in']);
        CLI::write(count($rows) . ' accounts. Listed on ' . date('Y-m-d') . '; keep this with the access review notes.');

        return EXIT_SUCCESS;
    }

    private function ban(string $email, bool $ban): int
    {
        $user = $this->find($email);

        if ($user === null) {
            return EXIT_USER_INPUT;
        }

        if ($ban) {
            $reason = $this->chosen['reason'];
            // Shield signs a banned user out on their next request.
            $user->ban($reason === '' ? null : $reason);
            service('auditLog')->record('staff.banned', null, 'user', (int) $user->id, $this->operator());
            CLI::write("{$email} can no longer sign in. Remove their diaspora access and reassign their inbox items too (docs/operations.md).", 'green');
        } else {
            $user->unBan();
            service('auditLog')->record('staff.unbanned', null, 'user', (int) $user->id, $this->operator());
            CLI::write("{$email} can sign in again.", 'green');
        }

        return EXIT_SUCCESS;
    }

    private function department(string $email, string $slug): int
    {
        $user = $this->find($email);

        if ($user === null) {
            return EXIT_USER_INPUT;
        }

        $department = db_connect()->table('departments')->where('slug', $slug)->get()->getRowArray();

        if ($department === null) {
            CLI::error("No department with the slug \"{$slug}\".");

            return EXIT_USER_INPUT;
        }

        $remove = $this->chosen['remove'];
        $access = service('departmentAccess');

        if ($remove) {
            $access->remove($user, (int) $department['id']);
        } else {
            $access->assign($user, (int) $department['id']);
        }

        service('auditLog')->record($remove ? 'staff.department-removed' : 'staff.department-added', null, 'user', (int) $user->id, ['department' => $slug] + $this->operator());
        CLI::write(($remove ? 'Removed ' : 'Added ') . "{$email} " . ($remove ? 'from ' : 'to ') . $department['name'] . '.', 'green');

        return EXIT_SUCCESS;
    }
}

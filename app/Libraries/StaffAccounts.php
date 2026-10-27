<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Shield\Authentication\Passwords\ValidationRules as PasswordRules;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Validation\ValidationRules;
use InvalidArgumentException;

/**
 * Creating staff accounts and changing their role or sign-in status, for the
 * staff screen (/admin/staff) and the maintenance page.
 *
 * Each account has one of the four roles. Nobody can change their own role
 * or ban themselves, and the last active Super Administrator cannot be
 * banned or moved to another role, so the site is never left without one.
 * Accounts are banned, never deleted (G5-04). Every change is audited.
 */
class StaffAccounts
{
    /**
     * @param array{username?: mixed, email?: mixed, password?: mixed, group?: mixed} $input
     * @param array<string, mixed>                                                  $audit extra audit details, such as where the change came from
     *
     * @return User|array<string, string> the new account, or validation errors
     */
    public function create(array $input, ?User $actor, array $audit = []): User|array
    {
        $rules = (new ValidationRules())->getRegistrationRules();
        unset($rules['password_confirm']);
        $rules['group'] = ['label' => 'Role', 'rules' => ['required', 'in_list[' . implode(',', $this->roles()) . ']']];

        // Shield's strong_password rule is not loaded by default, because
        // public registration is switched off.
        if (! in_array(PasswordRules::class, config('Validation')->ruleSets, true)) {
            config('Validation')->ruleSets[] = PasswordRules::class;
        }
        $validation = service('validation', null, false);
        $values     = array_map(static fn (mixed $v): string => is_string($v) ? trim($v) : '', [
            'username' => $input['username'] ?? '',
            'email'    => $input['email'] ?? '',
            'password' => $input['password'] ?? '',
            'group'    => $input['group'] ?? '',
        ]);
        // A password is used exactly as typed.
        $values['password'] = is_string($input['password'] ?? null) ? $input['password'] : '';

        if (! $validation->setRules($rules)->run($values)) {
            return $validation->getErrors();
        }

        $users = auth()->getProvider();
        $user  = new User(['username' => $values['username'], 'email' => $values['email'], 'password' => $values['password']]);
        $users->save($user);

        /** @var User $user */
        $user = $users->findById($users->getInsertID());
        $user->activate();
        $user->addGroup($values['group']);

        service('auditLog')->record('staff.created', $actor === null ? null : (int) $actor->id, 'user', (int) $user->id, ['group' => $values['group']] + $audit);

        return $user;
    }

    public function setRole(User $user, string $group, User $actor): void
    {
        if (! in_array($group, $this->roles(), true)) {
            throw new InvalidArgumentException('Choose one of the four roles.');
        }
        if ((int) $user->id === (int) $actor->id) {
            throw new InvalidArgumentException('You cannot change your own role. Ask another Super Administrator.');
        }
        if ($group !== 'superadmin' && $this->isLastSuperAdmin($user)) {
            throw new InvalidArgumentException('This is the only active Super Administrator. Give another account that role first.');
        }

        $before = $user->getGroups();
        $user->syncGroups($group);
        service('auditLog')->record('staff.role-changed', (int) $actor->id, 'user', (int) $user->id, ['from' => implode(',', $before), 'to' => $group, 'via' => 'staff screen']);
    }

    public function ban(User $user, string $reason, User $actor): void
    {
        if ((int) $user->id === (int) $actor->id) {
            throw new InvalidArgumentException('You cannot stop your own account from signing in.');
        }
        if ($this->isLastSuperAdmin($user)) {
            throw new InvalidArgumentException('This is the only active Super Administrator. Give another account that role first.');
        }

        // Shield signs a banned user out on their next request.
        $user->ban($reason === '' ? null : mb_substr($reason, 0, 190));
        service('auditLog')->record('staff.banned', (int) $actor->id, 'user', (int) $user->id, ['via' => 'staff screen']);
    }

    public function unban(User $user, User $actor): void
    {
        $user->unBan();
        service('auditLog')->record('staff.unbanned', (int) $actor->id, 'user', (int) $user->id, ['via' => 'staff screen']);
    }

    public function setDepartment(User $user, int $departmentId, bool $member, User $actor): void
    {
        $department = db_connect()->table('departments')->where('id', $departmentId)->get()->getRowArray();

        if ($department === null) {
            throw new InvalidArgumentException('Choose a department from the list.');
        }

        $access = service('departmentAccess');
        $member ? $access->assign($user, $departmentId) : $access->remove($user, $departmentId);

        service('auditLog')->record($member ? 'staff.department-added' : 'staff.department-removed', (int) $actor->id, 'user', (int) $user->id, ['department' => $department['slug'], 'via' => 'staff screen']);
    }

    /**
     * @return list<string>
     */
    public function roles(): array
    {
        return array_keys(config('AuthGroups')->groups);
    }

    public function activeSuperAdmins(): int
    {
        $count = 0;
        foreach (model(UserModel::class)->where('active', 1)->findAll() as $account) {
            if (! $account->isBanned() && $account->inGroup('superadmin')) {
                $count++;
            }
        }

        return $count;
    }

    private function isLastSuperAdmin(User $user): bool
    {
        return ! $user->isBanned() && $user->inGroup('superadmin') && $this->activeSuperAdmins() <= 1;
    }
}

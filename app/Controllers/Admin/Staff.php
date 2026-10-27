<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use InvalidArgumentException;

/**
 * Staff accounts screen for Super Administrators (users.manage): the
 * access-review list, new accounts, roles, banning and department
 * membership (brief section 3, G5-04). It does the same work as
 * `php spark staff`, for hosts without a terminal. Diaspora inbox access
 * stays on its own screen (PD-02).
 */
class Staff extends BaseController
{
    public function index(): string
    {
        $db          = db_connect();
        $departments = array_column($db->table('departments')->select('id, name')->orderBy('name')->get()->getResultArray(), 'name', 'id');
        $diaspora    = service('diasporaAccess')->holders();
        $rows        = [];

        foreach (model(UserModel::class)->withIdentities()->withGroups()->orderBy('username')->findAll() as $user) {
            $memberOf = array_map('intval', array_column($db->table('department_user')->where('user_id', $user->id)->get()->getResultArray(), 'department_id'));

            $rows[] = [
                'user'        => $user,
                'role'        => $user->getGroups()[0] ?? '',
                'departments' => array_intersect_key($departments, array_flip($memberOf)),
                'diaspora'    => $diaspora[(int) $user->id] ?? null,
                'lastSignIn'  => $db->table('auth_logins')->selectMax('date')->where(['user_id' => $user->id, 'success' => 1])->get()->getRow()?->date,
            ];
        }

        return view('admin/staff', [
            'rows'        => $rows,
            'groups'      => config('AuthGroups')->groups,
            'departments' => $departments,
            'selfId'      => (int) auth()->id(),
            'old'         => session('old') ?? [],
            'errors'      => session('errors') ?? [],
        ]);
    }

    public function create(): RedirectResponse
    {
        $result = service('staffAccounts')->create($this->request->getPost(['username', 'email', 'password', 'group']), auth()->user(), ['via' => 'staff screen']);

        if (is_array($result)) {
            return $this->back()->with('errors', $result)->with('old', $this->request->getPost(['username', 'email', 'group']));
        }

        return $this->back()->with('message', 'Created ' . $result->email . '. They sign in with the password you set and a code emailed to them.');
    }

    public function role(int $id): RedirectResponse
    {
        return $this->change($id, fn (User $user) => service('staffAccounts')->setRole($user, (string) $this->request->getPost('group'), auth()->user()), 'Role changed for %s.');
    }

    public function ban(int $id): RedirectResponse
    {
        return $this->change($id, fn (User $user) => service('staffAccounts')->ban($user, trim((string) $this->request->getPost('reason')), auth()->user()), '%s can no longer sign in. Remove their diaspora access and reassign their inbox items too.');
    }

    public function unban(int $id): RedirectResponse
    {
        return $this->change($id, fn (User $user) => service('staffAccounts')->unban($user, auth()->user()), '%s can sign in again.');
    }

    public function department(int $id): RedirectResponse
    {
        $member = $this->request->getPost('action') !== 'remove';

        return $this->change($id, fn (User $user) => service('staffAccounts')->setDepartment($user, (int) $this->request->getPost('department_id'), $member, auth()->user()), $member ? 'Department added for %s.' : 'Department removed for %s.');
    }

    private function change(int $id, callable $action, string $message): RedirectResponse
    {
        $user = $id > 0 ? model(UserModel::class)->findById($id) : null;

        if ($user === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            $action($user);
        } catch (InvalidArgumentException $e) {
            return $this->back()->with('error', $e->getMessage());
        }

        return $this->back()->with('message', sprintf($message, $user->username));
    }

    private function back(): RedirectResponse
    {
        return redirect()->to(site_url('admin/staff'));
    }
}

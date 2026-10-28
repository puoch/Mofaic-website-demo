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
 * Super Administrators grant or remove per-person access to the Diaspora
 * inbox; they have it themselves by role (sponsor, 28 October 2026). Only
 * central Ministry administrators and members of a diaspora department can
 * receive it, and nobody can grant it to themselves.
 */
class DiasporaAccess extends BaseController
{
    public function index(): string
    {
        $access    = service('diasporaAccess');
        $holders   = $access->holders();
        $eligible  = [];
        $staff     = model(UserModel::class)->where('active', 1)->orderBy('username')->findAll();

        foreach ($staff as $user) {
            if ((int) $user->id !== (int) auth()->id() && ! isset($holders[(int) $user->id]) && $access->isEligible($user)) {
                $eligible[(int) $user->id] = \App\Libraries\StaffDirectory::label($user);
            }
        }

        return view('admin/diaspora_access', [
            'holders'  => $holders,
            'labels'   => service('staffDirectory')->all(),
            'eligible' => $eligible,
        ]);
    }

    public function grant(): RedirectResponse
    {
        $level = (string) $this->request->getPost('level');
        $user  = $this->target((int) $this->request->getPost('user_id'));

        if (! in_array($level, ['view', 'manage'], true)) {
            return redirect()->to(site_url('admin/diaspora-access'))->with('error', 'Choose view or manage.');
        }

        try {
            service('diasporaAccess')->grant($user, $level, auth()->user());
        } catch (InvalidArgumentException $e) {
            return redirect()->to(site_url('admin/diaspora-access'))->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('admin/diaspora-access'))->with('message', 'Access granted to ' . $user->username . '.');
    }

    public function revoke(int $userId): RedirectResponse
    {
        $user = $this->target($userId);
        service('diasporaAccess')->revoke($user, auth()->user());

        return redirect()->to(site_url('admin/diaspora-access'))->with('message', 'Access removed from ' . $user->username . '.');
    }

    private function target(int $id): User
    {
        $user = $id > 0 ? model(UserModel::class)->findById($id) : null;

        if ($user === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $user;
    }
}

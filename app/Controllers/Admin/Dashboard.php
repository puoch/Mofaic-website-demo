<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\AuthGroups;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $user   = auth()->user();
        $groups = config(AuthGroups::class);

        $departmentIds = service('departmentAccess')->departmentIdsFor($user);
        $departments   = $departmentIds === []
            ? []
            : db_connect()->table('departments')->whereIn('id', $departmentIds)->orderBy('name')->get()->getResultArray();

        $permissions = array_filter(
            $groups->permissions,
            static fn (string $permission): bool => $user->can($permission),
            ARRAY_FILTER_USE_KEY,
        );

        return view('admin/dashboard', [
            'user'        => $user,
            'roles'       => array_map(static fn (string $g): string => $groups->groups[$g]['title'] ?? $g, $user->getGroups()),
            'departments' => $departments,
            'permissions' => $permissions,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Lists active staff accounts, for example everyone who may be assigned a
 * submission.
 */
class StaffDirectory
{
    /**
     * @return array<int, string> user id => label
     */
    public function withPermission(string $permission): array
    {
        $staff = [];

        foreach (model(UserModel::class)->where('active', 1)->orderBy('username')->findAll() as $user) {
            if ($user->can($permission)) {
                $staff[(int) $user->id] = self::label($user);
            }
        }

        return $staff;
    }

    /**
     * @return array<int, string>
     */
    public function all(): array
    {
        $staff = [];
        foreach (model(UserModel::class)->orderBy('username')->findAll() as $user) {
            $staff[(int) $user->id] = self::label($user);
        }

        return $staff;
    }

    public static function label(User $user): string
    {
        $email = $user->email;

        return $email !== null && $email !== '' ? $user->username . ' (' . $email . ')' : (string) $user->username;
    }
}

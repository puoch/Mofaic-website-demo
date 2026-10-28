<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Shield\Entities\User;
use InvalidArgumentException;

/**
 * Per-person access to the Diaspora inbox (brief section 3). Only the Super
 * Administrator role includes it (sponsor, 28 October 2026). A Super
 * Administrator grants it to a named central administrator or a member of a
 * diaspora department, and every grant and removal is audited.
 */
class DiasporaAccess
{
    public const VIEW   = 'diaspora.submissions-view';
    public const MANAGE = 'diaspora.submissions-manage';

    public function __construct(
        private readonly BaseConnection $db,
        private readonly DepartmentAccess $departments,
        private readonly AuditLog $audit,
    ) {
    }

    /**
     * Central Ministry administrators and members of a diaspora department.
     */
    public function isEligible(User $user): bool
    {
        if ($user->inGroup('ministryadmin')) {
            return true;
        }

        $ids = $this->departments->departmentIdsFor($user);

        return $ids !== [] && $this->db->table('departments')->whereIn('id', $ids)->where('is_diaspora', 1)->countAllResults() > 0;
    }

    /**
     * @param 'view'|'manage' $level manage includes view
     */
    public function grant(User $target, string $level, User $actor): void
    {
        if ((int) $target->id === (int) $actor->id) {
            throw new InvalidArgumentException('You cannot grant diaspora access to yourself.');
        }

        if (! $this->isEligible($target)) {
            throw new InvalidArgumentException('Diaspora access is only for central Ministry administrators and members of a diaspora department.');
        }

        $permissions = $level === 'manage' ? [self::VIEW, self::MANAGE] : [self::VIEW];
        $target->addPermission(...$permissions);
        $this->audit->record('diaspora.access-granted', (int) $actor->id, 'user', (int) $target->id, ['level' => $level]);
    }

    public function revoke(User $target, User $actor): void
    {
        $target->removePermission(self::VIEW, self::MANAGE);
        $this->audit->record('diaspora.access-removed', (int) $actor->id, 'user', (int) $target->id);
    }

    /**
     * Staff who hold a per-person grant.
     *
     * @return array<int, string> user id => 'view' | 'manage'
     */
    public function holders(): array
    {
        $holders = [];
        $rows    = $this->db->table('auth_permissions_users')
            ->whereIn('permission', [self::VIEW, self::MANAGE])
            ->get()
            ->getResultArray();

        foreach ($rows as $row) {
            $id = (int) $row['user_id'];
            if ($row['permission'] === self::MANAGE || ! isset($holders[$id])) {
                $holders[$id] = $row['permission'] === self::MANAGE ? 'manage' : 'view';
            }
        }

        return $holders;
    }
}

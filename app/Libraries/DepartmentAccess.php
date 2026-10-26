<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\ConnectionInterface;
use CodeIgniter\Shield\Entities\User;

/**
 * Decides whether a staff member may publish in a department.
 *
 * Brief section 3: authorized editors publish directly within their permitted
 * scope, with no default second approval. Department Editors may belong to
 * several departments.
 */
class DepartmentAccess
{
    public function __construct(private readonly ConnectionInterface $db)
    {
    }

    /**
     * @return list<int>
     */
    public function departmentIdsFor(User $user): array
    {
        $rows = $this->db->table('department_user')
            ->select('department_id')
            ->where('user_id', $user->id)
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): int => (int) $row['department_id'], $rows);
    }

    public function isMember(User $user, int $departmentId): bool
    {
        return in_array($departmentId, $this->departmentIdsFor($user), true);
    }

    public function canPublishIn(User $user, int $departmentId): bool
    {
        if ($user->can('content.publish-any')) {
            return true;
        }

        return $user->can('content.publish-department') && $this->isMember($user, $departmentId);
    }

    /**
     * Mission activities are published only by central Ministry administrators
     * (brief sections 2 and 3), whatever department membership a user has.
     */
    public function canPublishMissionActivities(User $user): bool
    {
        return $user->can('missions.publish-activities');
    }

    public function assign(User $user, int $departmentId): void
    {
        if ($this->isMember($user, $departmentId)) {
            return;
        }

        $this->db->table('department_user')->insert([
            'department_id' => $departmentId,
            'user_id'       => $user->id,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }

    public function remove(User $user, int $departmentId): void
    {
        $this->db->table('department_user')
            ->where(['department_id' => $departmentId, 'user_id' => $user->id])
            ->delete();
    }
}

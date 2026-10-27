<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Shield\Entities\User;
use Config\ContentTypes;

/**
 * Who may create, change, publish and restore each content type.
 *
 * Rules (brief sections 2 and 3):
 *  - Authorized editors publish directly in their scope; there is no second
 *    approval step.
 *  - Department Editors work only in departments they belong to.
 *  - Mission activities are published only by central Ministry administrators.
 *  - Mission records, countries and site-wide lists belong to central
 *    Ministry administrators.
 *  - Diaspora content belongs to a diaspora department, so it is published
 *    by that department's editors (and central administrators).
 */
class ContentPolicy
{
    public function __construct(
        private readonly ContentTypes $types,
        private readonly DepartmentAccess $departments,
    ) {
    }

    public function canView(User $user): bool
    {
        return $user->can('content.view');
    }

    /**
     * May the user save or publish a record of this type with these values?
     *
     * @param array<string, mixed>|null $record Field values (new or existing)
     */
    public function canEdit(User $user, string $type, ?array $record = null): bool
    {
        $scope = $this->types->type($type)['scope'] ?? null;

        return match ($scope) {
            'missions'   => $user->can('missions.manage'),
            'site'       => $user->can('content.publish-any'),
            'department' => $this->canEditInDepartment($user, $type, $record ?? []),
            default      => false,
        };
    }

    /**
     * May the user start a new record of this type at all?
     */
    public function canCreate(User $user, string $type): bool
    {
        if (! empty($this->types->type($type)['fixedRows'])) {
            return false;
        }

        $scope = $this->types->type($type)['scope'] ?? null;

        if ($scope === 'department') {
            return $user->can('content.publish-any')
                || ($user->can('content.publish-department') && $this->departments->departmentIdsFor($user) !== [])
                || ($type === 'articles' && $user->can('missions.publish-activities'));
        }

        return $this->canEdit($user, $type);
    }

    /**
     * Diaspora announcements, events, newsletters and Diaspora by Country pages.
     *
     * @param array<string, mixed> $record
     */
    public function isDiasporaContent(string $type, array $record): bool
    {
        if (! empty($this->types->type($type)['diaspora'])) {
            return true;
        }

        return $type === 'articles' && in_array($record['category'] ?? null, $this->types->diasporaArticleCategories, true);
    }

    /**
     * May the user publish diaspora content in at least one department?
     */
    public function canPublishDiaspora(User $user): bool
    {
        $diaspora = $this->departments->diasporaDepartmentIds();
        $allowed  = $this->allowedDepartmentIds($user);

        return $diaspora !== [] && ($allowed === null || array_intersect($diaspora, $allowed) !== []);
    }

    public function canRestore(User $user, string $type, array $record): bool
    {
        return $user->can('content.restore') && $this->canEdit($user, $type, $record);
    }

    /**
     * Departments the user may choose for department-scoped content.
     *
     * @return list<int>|null null means every department
     */
    public function allowedDepartmentIds(User $user): ?array
    {
        if ($user->can('content.publish-any')) {
            return null;
        }

        return $user->can('content.publish-department') ? $this->departments->departmentIdsFor($user) : [];
    }

    /**
     * @param array<string, mixed> $record
     */
    private function canEditInDepartment(User $user, string $type, array $record): bool
    {
        if ($type === 'articles' && ($record['category'] ?? null) === 'mission_activity') {
            return $this->departments->canPublishMissionActivities($user);
        }

        $departmentId = isset($record['department_id']) && $record['department_id'] !== '' ? (int) $record['department_id'] : null;

        if ($this->isDiasporaContent($type, $record)) {
            return $departmentId !== null
                && $this->departments->isDiasporaDepartment($departmentId)
                && $this->departments->canPublishIn($user, $departmentId);
        }

        if ($departmentId === null) {
            return $user->can('content.publish-any');
        }

        return $this->departments->canPublishIn($user, $departmentId);
    }
}

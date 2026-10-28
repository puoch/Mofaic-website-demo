<?php

declare(strict_types=1);

namespace Tests\Unit;

use Config\AuthGroups;
use Tests\Support\StaffTestCase;

/**
 * Role permissions and department scope (GOV-01 to GOV-04, HOME-03).
 */
final class PermissionsTest extends StaffTestCase
{
    public function testThereAreExactlyTheFourConfirmedRoles(): void
    {
        $titles = array_column(config(AuthGroups::class)->groups, 'title');

        $this->assertSame(['Super Administrator', 'Ministry Administrator', 'Department Editor', 'Read-Only Staff'], $titles);
    }

    public function testNewAccountsDefaultToReadOnly(): void
    {
        $this->assertSame('readonly', config(AuthGroups::class)->defaultGroup);
    }

    public function testOnlySuperAdministratorsGetDiasporaSubmissionsByRole(): void
    {
        foreach (config(AuthGroups::class)->matrix as $group => $permissions) {
            foreach ($permissions as $permission) {
                if ($group !== 'superadmin') {
                    $this->assertStringStartsNotWith('diaspora.', $permission, "{$group} must not have {$permission} by role");
                }
                $this->assertNotSame('*', $permission);
            }
        }

        $this->assertTrue($this->staff('superadmin')->can('diaspora.submissions-view'));
        $this->assertFalse($this->staff('ministryadmin')->can('diaspora.submissions-view'));
    }

    public function testDiasporaSubmissionsAccessIsGrantedPerPerson(): void
    {
        $user = $this->staff('editor');
        $user->addPermission('diaspora.submissions-view');

        $this->assertTrue($user->can('diaspora.submissions-view'));
        $this->assertFalse($this->staff('editor')->can('diaspora.submissions-view'));
    }

    public function testEditorPublishesOnlyInTheirDepartments(): void
    {
        $a        = $this->department('a');
        $b        = $this->department('b');
        $c        = $this->department('c');
        $editor   = $this->staff('editor');
        $access   = service('departmentAccess');

        $access->assign($editor, $a);
        $access->assign($editor, $b);

        $this->assertTrue($access->canPublishIn($editor, $a));
        $this->assertTrue($access->canPublishIn($editor, $b));
        $this->assertFalse($access->canPublishIn($editor, $c));
        $this->assertSame([$a, $b], $access->departmentIdsFor($editor));
    }

    public function testAssigningTwiceKeepsOneMembership(): void
    {
        $a      = $this->department('a');
        $editor = $this->staff('editor');
        service('departmentAccess')->assign($editor, $a);
        service('departmentAccess')->assign($editor, $a);

        $this->assertSame([$a], service('departmentAccess')->departmentIdsFor($editor));
    }

    public function testRemovingMembershipRemovesPublishing(): void
    {
        $a      = $this->department('a');
        $editor = $this->staff('editor');
        service('departmentAccess')->assign($editor, $a);
        service('departmentAccess')->remove($editor, $a);

        $this->assertFalse(service('departmentAccess')->canPublishIn($editor, $a));
    }

    public function testMinistryAdministratorPublishesInAnyDepartment(): void
    {
        $a = $this->department('a');

        $this->assertTrue(service('departmentAccess')->canPublishIn($this->staff('ministryadmin'), $a));
    }

    public function testReadOnlyStaffCannotPublish(): void
    {
        $a        = $this->department('a');
        $readonly = $this->staff('readonly');
        service('departmentAccess')->assign($readonly, $a);

        $this->assertFalse(service('departmentAccess')->canPublishIn($readonly, $a));
    }

    public function testSuperAdministratorsHaveEveryRightOfTheOtherRoles(): void
    {
        $matrix = config(AuthGroups::class)->matrix;

        foreach ($matrix as $group => $permissions) {
            $this->assertSame([], array_values(array_diff($permissions, $matrix['superadmin'])), "superadmin lacks a right of {$group}");
        }
    }

    public function testOnlyAdministratorsPublishMissionActivities(): void
    {
        $diaspora = $this->department('diaspora', true);
        $editor   = $this->staff('editor');
        service('departmentAccess')->assign($editor, $diaspora);

        $access = service('departmentAccess');
        $this->assertTrue($access->canPublishMissionActivities($this->staff('ministryadmin')));
        $this->assertFalse($access->canPublishMissionActivities($editor));
        $this->assertTrue($access->canPublishMissionActivities($this->staff('superadmin')));
        $this->assertFalse($access->canPublishMissionActivities($this->staff('readonly')));
    }
}

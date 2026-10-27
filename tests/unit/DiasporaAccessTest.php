<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use Tests\Support\StaffTestCase;

/**
 * Per-person access to diaspora submissions (GOV-04; PD-02 accepted).
 */
final class DiasporaAccessTest extends StaffTestCase
{
    public function testNoRoleOpensTheDiasporaInboxByItself(): void
    {
        foreach (['superadmin', 'ministryadmin', 'editor', 'readonly'] as $role) {
            $this->assertFalse($this->staff($role)->can('diaspora.submissions-view'), $role);
        }
    }

    public function testCentralAdministratorsAndDiasporaEditorsAreEligible(): void
    {
        $access   = service('diasporaAccess');
        $diaspora = $this->department('diaspora', true);
        $other    = $this->department('other');

        $this->assertTrue($access->isEligible($this->staff('ministryadmin')));
        $this->assertTrue($access->isEligible($this->editorIn($diaspora)));
        $this->assertFalse($access->isEligible($this->editorIn($other)));
        $this->assertFalse($access->isEligible($this->staff('readonly')));
        $this->assertFalse($access->isEligible($this->staff('superadmin')));
    }

    public function testGrantingAndRemovingIsAudited(): void
    {
        $super  = $this->staff('superadmin');
        $target = $this->staff('ministryadmin');
        $access = service('diasporaAccess');

        $access->grant($target, 'manage', $super);
        $this->assertTrue($target->can('diaspora.submissions-view'));
        $this->assertTrue($target->can('diaspora.submissions-manage'));
        $this->assertSame([(int) $target->id => 'manage'], $access->holders());

        $access->revoke($target, $super);
        $this->assertFalse($target->can('diaspora.submissions-view'));
        $this->assertSame(
            ['diaspora.access-granted', 'diaspora.access-removed'],
            array_column($this->db->table('audit_events')->like('event', 'diaspora.access', 'after')->orderBy('id')->get()->getResultArray(), 'event'),
        );
    }

    public function testNobodyCanGrantAccessToThemselves(): void
    {
        $admin = $this->staff('ministryadmin');
        $admin->addGroup('superadmin');

        $this->expectException(InvalidArgumentException::class);
        service('diasporaAccess')->grant($admin, 'view', $admin);
    }

    public function testIneligibleStaffCannotBeGranted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        service('diasporaAccess')->grant($this->staff('readonly'), 'view', $this->staff('superadmin'));
    }

    private function editorIn(int $department): \CodeIgniter\Shield\Entities\User
    {
        $editor = $this->staff('editor');
        service('departmentAccess')->assign($editor, $department);

        return $editor;
    }
}

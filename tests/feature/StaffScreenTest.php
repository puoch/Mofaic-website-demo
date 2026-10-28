<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use Tests\Support\StaffTestCase;

/**
 * The staff accounts screen (GOV-01, SEC-04, G5-04): only Super
 * Administrators reach it; accounts are created, re-roled, banned and placed
 * in departments, every change is audited, and nobody can lock themselves
 * or the site out of Super Administrator access.
 *
 * @internal
 */
final class StaffScreenTest extends StaffTestCase
{
    public function testOnlySuperAdministratorsOpenTheScreen(): void
    {
        foreach (['ministryadmin', 'editor', 'readonly'] as $role) {
            $this->actingAs($this->staff($role))->get('admin/staff')->assertRedirect();
        }

        $super = $this->staff('superadmin');
        $page  = $this->actingAs($super)->get('admin/staff');
        $page->assertOK();
        $page->assertSee($super->username);
        $page->assertSee('Staff accounts');
    }

    public function testCreatesAnAccountWithARoleAndAuditsIt(): void
    {
        $super = $this->staff('superadmin');

        $this->postAs($super, 'admin/staff', [
            'username' => 'neweditor',
            'email'    => 'new-editor@example.invalid',
            'password' => 'a long and unusual passphrase 42',
            'group'    => 'editor',
        ])->assertRedirectTo(site_url('admin/staff'));

        $user = model(UserModel::class)->findByCredentials(['email' => 'new-editor@example.invalid']);
        $this->assertNotNull($user);
        $this->assertTrue($user->inGroup('editor'));
        $this->assertTrue($user->isActivated());

        $event = $this->db->table('audit_events')->where('event', 'staff.created')->get()->getRowArray();
        $this->assertSame((string) $super->id, (string) $event['actor_user_id']);
        $this->assertStringNotContainsString('passphrase', (string) $event['details']);
    }

    public function testRefusesWeakPasswordsAndUnknownRoles(): void
    {
        $super = $this->staff('superadmin');

        $this->postAs($super, 'admin/staff', ['username' => 'weak', 'email' => 'weak@example.invalid', 'password' => 'short', 'group' => 'editor']);
        $this->postAs($super, 'admin/staff', ['username' => 'odd-role', 'email' => 'odd@example.invalid', 'password' => 'a long and unusual passphrase 42', 'group' => 'owner']);

        $this->assertNull(model(UserModel::class)->findByCredentials(['email' => 'weak@example.invalid']));
        $this->assertNull(model(UserModel::class)->findByCredentials(['email' => 'odd@example.invalid']));
        $this->assertSame(0, $this->db->table('audit_events')->where('event', 'staff.created')->countAllResults());
    }

    public function testChangesARoleButNeverOnesOwn(): void
    {
        $super  = $this->staff('superadmin');
        $editor = $this->staff('editor');

        $this->postAs($super, 'admin/staff/' . $editor->id . '/role', ['group' => 'ministryadmin']);
        $this->assertSame(['ministryadmin'], $this->reload($editor)->getGroups());
        $this->assertSame(1, $this->db->table('audit_events')->where('event', 'staff.role-changed')->countAllResults());

        $this->postAs($super, 'admin/staff/' . $super->id . '/role', ['group' => 'readonly']);
        $this->assertSame(['superadmin'], $this->reload($super)->getGroups());
    }

    public function testTheLastSuperAdministratorCannotBeRemoved(): void
    {
        $first  = $this->staff('superadmin');
        $second = $this->staff('superadmin');

        // Two Super Administrators: one may move the other to another role.
        $this->postAs($first, 'admin/staff/' . $second->id . '/role', ['group' => 'readonly']);
        $this->assertSame(['readonly'], $this->reload($second)->getGroups());

        // Now $first is the only one left and keeps the role.
        $this->expectException(\InvalidArgumentException::class);
        service('staffAccounts')->setRole($this->reload($first), 'readonly', $this->staff('ministryadmin'));
    }

    public function testBanningIsRefusedForTheOnlySuperAdministrator(): void
    {
        $only  = $this->staff('superadmin');
        $actor = $this->staff('ministryadmin');

        $this->expectException(\InvalidArgumentException::class);
        service('staffAccounts')->ban($only, '', $actor);
    }

    public function testBansAndUnbansAnotherAccountButNotOneself(): void
    {
        $super  = $this->staff('superadmin');
        $leaver = $this->staff('editor');

        $this->postAs($super, 'admin/staff/' . $leaver->id . '/ban', ['reason' => 'Left the Ministry']);
        $this->assertTrue($this->reload($leaver)->isBanned());
        $this->assertSame('Left the Ministry', $this->reload($leaver)->getBanMessage());

        $this->postAs($super, 'admin/staff/' . $leaver->id . '/unban');
        $this->assertFalse($this->reload($leaver)->isBanned());

        $this->postAs($super, 'admin/staff/' . $super->id . '/ban');
        $this->assertFalse($this->reload($super)->isBanned());

        $this->assertSame(1, $this->db->table('audit_events')->where('event', 'staff.banned')->countAllResults());
        $this->assertSame(1, $this->db->table('audit_events')->where('event', 'staff.unbanned')->countAllResults());
    }

    public function testAddsAndRemovesDepartmentMembership(): void
    {
        $super      = $this->staff('superadmin');
        $editor     = $this->staff('editor');
        $department = $this->department('trade');

        $this->postAs($super, 'admin/staff/' . $editor->id . '/department', ['department_id' => (string) $department]);
        $this->assertTrue(service('departmentAccess')->isMember($editor, $department));

        $this->postAs($super, 'admin/staff/' . $editor->id . '/department', ['department_id' => (string) $department, 'action' => 'remove']);
        $this->assertFalse(service('departmentAccess')->isMember($editor, $department));

        $this->postAs($super, 'admin/staff/' . $editor->id . '/department', ['department_id' => '999']);
        $this->assertSame(2, $this->db->table('audit_events')->like('event', 'staff.department-', 'after')->countAllResults());
    }

    public function testUnknownAccountsReturn404(): void
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->postAs($this->staff('superadmin'), 'admin/staff/999/ban');
    }

    private function reload(User $user): User
    {
        return model(UserModel::class)->findById($user->id);
    }
}

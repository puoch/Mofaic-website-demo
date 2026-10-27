<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\StaffTestCase;

/**
 * Access review and offboarding from the command line (docs/operations.md).
 */
final class StaffCommandTest extends StaffTestCase
{
    use StreamFilterTrait;

    private function reload(int $id): \CodeIgniter\Shield\Entities\User
    {
        return model(UserModel::class)->findById($id);
    }

    public function testListsEveryAccountWithRolesAndDepartments(): void
    {
        $editor = $this->staff('editor', 'listed-editor@example.invalid');
        service('departmentAccess')->assign($editor, $this->department('test-dept'));

        command('staff list');
        $output = $this->getStreamFilterBuffer();

        $this->assertStringContainsString('listed-editor@example.invalid', $output);
        $this->assertStringContainsString('test-dept', $output);
        $this->assertStringContainsString('never', $output);
    }

    public function testBanStopsSignInAndIsAudited(): void
    {
        $user = $this->staff('editor', 'leaver@example.invalid');

        command('staff ban leaver@example.invalid --reason "Left the Ministry"');

        $this->assertTrue($this->reload((int) $user->id)->isBanned());
        $this->assertSame('Left the Ministry', $this->reload((int) $user->id)->getBanMessage());
        $this->seeInDatabase('audit_events', ['event' => 'staff.banned', 'subject_id' => (string) $user->id]);

        command('staff unban leaver@example.invalid');

        $this->assertFalse($this->reload((int) $user->id)->isBanned());
        $this->seeInDatabase('audit_events', ['event' => 'staff.unbanned', 'subject_id' => (string) $user->id]);
    }

    public function testAddsAndRemovesDepartmentMembership(): void
    {
        $user = $this->staff('editor', 'mover@example.invalid');
        $id   = $this->department('consular');

        command('staff department mover@example.invalid consular');
        $this->seeInDatabase('department_user', ['user_id' => $user->id, 'department_id' => $id]);
        $this->seeInDatabase('audit_events', ['event' => 'staff.department-added', 'subject_id' => (string) $user->id]);

        command('staff department mover@example.invalid consular --remove');
        $this->dontSeeInDatabase('department_user', ['user_id' => $user->id, 'department_id' => $id]);
        $this->seeInDatabase('audit_events', ['event' => 'staff.department-removed', 'subject_id' => (string) $user->id]);
    }

    public function testUnknownAccountsAndDepartmentsChangeNothing(): void
    {
        $this->staff('editor', 'present@example.invalid');

        command('staff ban absent@example.invalid');
        command('staff department present@example.invalid no-such-department');

        $this->assertStringContainsString('No staff account', $this->getStreamFilterBuffer());
        $this->dontSeeInDatabase('audit_events', ['event' => 'staff.banned']);
        $this->dontSeeInDatabase('audit_events', ['event' => 'staff.department-added']);
    }
}

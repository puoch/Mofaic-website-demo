<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Shield\Entities\User;
use Tests\Support\StaffTestCase;

/**
 * Staff inboxes: separation, per-person diaspora access, statuses,
 * assignment and staff-only notes (CON-04, CON-05, DIA-13, GOV-04, SEC-04).
 */
final class InboxTest extends StaffTestCase
{
    private int $registration;
    private int $contribution;
    private int $inquiry;

    protected function setUp(): void
    {
        parent::setUp();
        $country            = $this->country('XA', 'Testland');
        $this->registration = service('submissions')->create('registration', ['name' => 'Registrant (test)', 'city' => 'Test City', 'country_id' => $country, 'email' => 'r@example.invalid'], 'en')['id'];
        $this->contribution = service('submissions')->create('contribution', ['category' => 'feedback', 'message' => 'Contribution text (test)'], 'en')['id'];
        $this->inquiry      = service('submissions')->create('inquiry', ['name' => 'Inquirer (test)', 'email' => 'i@example.invalid', 'message' => 'Inquiry text (test)'], 'en')['id'];
    }

    public function testOnlySuperAdministratorsOpenTheDiasporaInboxWithoutAGrant(): void
    {
        $super = $this->staff('superadmin');
        $this->actingAs($super)->get('admin/diaspora')->assertOK();
        $this->actingAs($super)->get('admin/diaspora/registration/' . $this->registration)->assertSee('Registrant (test)');
        $this->assertSame(1, $this->db->table('audit_events')->where(['event' => 'submission.viewed', 'actor_user_id' => $super->id])->countAllResults());
        $this->db->table('audit_events')->where('event', 'submission.viewed')->delete();

        foreach (['ministryadmin', 'editor', 'readonly'] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get('admin/diaspora')->assertStatus(403);
            $this->actingAs($user)->get('admin/diaspora/registration/' . $this->registration)->assertStatus(403);
        }
        $this->assertSame(0, $this->db->table('audit_events')->where('event', 'submission.viewed')->countAllResults());
    }

    public function testAGrantedPersonSeesBothDiasporaTypesAndTheViewIsAudited(): void
    {
        $viewer = $this->granted('view');

        $list = $this->actingAs($viewer)->get('admin/diaspora')->getBody();
        $this->assertStringContainsString('REG-', $list);
        $this->assertStringNotContainsString('Registrant (test)', $list, 'Lists show references, not names.');
        $this->assertStringContainsString('CON-', $this->actingAs($viewer)->get('admin/diaspora?type=contribution')->getBody());
        $this->assertStringNotContainsString('INQ-', $list);

        $this->actingAs($viewer)->get('admin/diaspora/registration/' . $this->registration)->assertSee('Registrant (test)');
        $this->assertSame(1, $this->db->table('audit_events')->where(['event' => 'submission.viewed', 'actor_user_id' => $viewer->id])->countAllResults());

        $this->actingAs($viewer)->get('admin/inquiries')->assertStatus(403);
    }

    public function testViewOnlyAccessCannotChangeAnything(): void
    {
        $viewer = $this->granted('view');
        $base   = 'admin/diaspora/registration/' . $this->registration;

        $this->assertStringNotContainsString('name="status"', $this->actingAs($viewer)->get($base)->getBody());
        $this->postAs($viewer, $base . '/status', ['status' => 'closed'])->assertStatus(403);
        $this->postAs($viewer, $base . '/notes', ['note' => 'x'])->assertStatus(403);
        $this->assertSame('new', $this->db->table('diaspora_registrations')->get()->getRow()->status);
    }

    public function testHandlingASubmission(): void
    {
        $handler = $this->granted('manage');
        $other   = $this->granted('view');
        $base    = 'admin/diaspora/registration/' . $this->registration;

        $this->postAs($handler, $base . '/status', ['status' => 'in_progress'])->assertRedirectTo(site_url($base));
        $this->postAs($handler, $base . '/status', ['status' => 'deleted']);
        $this->assertSame('in_progress', $this->db->table('diaspora_registrations')->get()->getRow()->status);

        $this->postAs($handler, $base . '/assign', ['assigned_to' => (string) $other->id]);
        $this->assertSame((int) $other->id, (int) $this->db->table('diaspora_registrations')->get()->getRow()->assigned_to);

        // Staff who cannot open the inbox cannot be made responsible.
        $outsider = $this->staff('readonly');
        $this->postAs($handler, $base . '/assign', ['assigned_to' => (string) $outsider->id]);
        $this->assertSame((int) $other->id, (int) $this->db->table('diaspora_registrations')->get()->getRow()->assigned_to);

        $this->postAs($handler, $base . '/notes', ['note' => 'Staff-only note (test)']);
        $this->actingAs($other)->get($base)->assertSee('Staff-only note (test)');

        $this->actingAs($other)->get('admin/diaspora?mine=1')->assertSee('REG-');
        $this->actingAs($handler)->get('admin/diaspora?mine=1')->assertSee('data-testid="inbox-empty"', null);

        foreach (['submission.status-changed', 'submission.assigned', 'submission.note-added'] as $event) {
            $this->assertGreaterThan(0, $this->db->table('audit_events')->where(['event' => $event, 'actor_user_id' => $handler->id])->countAllResults(), $event);
        }
    }

    public function testInboxesDoNotShowEachOthersSubmissions(): void
    {
        $handler = $this->granted('manage');
        $admin   = $this->staff('ministryadmin');

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->actingAs($admin)->get('admin/inquiries/registration/' . $this->registration);
    }

    public function testSuperAdministratorsManageGrantsOnTheAccessScreen(): void
    {
        $super = $this->staff('superadmin');
        $admin = $this->staff('ministryadmin');

        $this->actingAs($admin)->get('admin/diaspora-access')->assertRedirect();
        $page = $this->actingAs($super)->get('admin/diaspora-access')->getBody();
        $this->assertStringContainsString($admin->username, $page);
        $this->assertDoesNotMatchRegularExpression('/<option value="' . $super->id . '">/', $page, 'Super Administrators are not offered themselves.');

        $this->postAs($super, 'admin/diaspora-access', ['user_id' => (string) $admin->id, 'level' => 'view'])->assertRedirectTo(site_url('admin/diaspora-access'));
        $this->actingAs($this->reload($admin))->get('admin/diaspora')->assertOK();

        $this->postAs($super, 'admin/diaspora-access/' . $admin->id . '/remove');
        $this->actingAs($this->reload($admin))->get('admin/diaspora')->assertStatus(403);
    }

    /**
     * A diaspora department editor with a per-person grant.
     */
    private function granted(string $level): User
    {
        $user = $this->staff('editor');
        service('departmentAccess')->assign($user, $this->department('diaspora-' . bin2hex(random_bytes(2)), true));
        service('diasporaAccess')->grant($user, $level, $this->staff('superadmin'));

        return $user;
    }

    private function reload(User $user): User
    {
        return model(\CodeIgniter\Shield\Models\UserModel::class)->findById($user->id);
    }
}

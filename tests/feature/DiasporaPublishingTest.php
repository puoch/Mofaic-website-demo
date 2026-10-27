<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Shield\Entities\User;
use Tests\Support\StaffTestCase;

/**
 * Diaspora content is published directly by diaspora department editors
 * (DIA-09, DIA-11, DIA-12), and newsletters are sent from the staff area.
 */
final class DiasporaPublishingTest extends StaffTestCase
{
    private int $diaspora;
    private int $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->diaspora = $this->department('diaspora', true);
        $this->other    = $this->department('other');
    }

    public function testDiasporaEditorsPublishDiasporaAnnouncementsDirectly(): void
    {
        $editor = $this->editorIn($this->diaspora);

        $this->postAs($editor, 'admin/content/articles', [
            'category' => 'diaspora_announcement', 'title' => 'Announcement (test)', 'department_id' => (string) $this->diaspora,
        ]);
        $id = (int) $this->db->table('articles')->where('title', 'Announcement (test)')->get()->getRow()->id;
        $this->postAs($editor, "admin/content/articles/{$id}/publish");

        $this->assertSame('published', $this->db->table('articles')->where('id', $id)->get()->getRow()->status);
    }

    public function testDiasporaContentMustBelongToADiasporaDepartment(): void
    {
        $editor = $this->editorIn($this->diaspora, $this->other);

        $this->postAs($editor, 'admin/content/articles', [
            'category' => 'diaspora_event', 'title' => 'Wrong department (test)', 'department_id' => (string) $this->other,
        ])->assertSee('Diaspora content must belong to a diaspora department.');
        $this->assertSame(0, $this->db->table('articles')->countAllResults());
    }

    public function testOtherEditorsAreNotOfferedDiasporaCategories(): void
    {
        $editor = $this->editorIn($this->other);

        $form = $this->actingAs($editor)->get('admin/content/articles/new')->getBody();
        $this->assertStringNotContainsString('value="diaspora_announcement"', $form);

        $this->postAs($editor, 'admin/content/articles', [
            'category' => 'diaspora_announcement', 'title' => 'Not allowed (test)', 'department_id' => (string) $this->other,
        ]);
        $this->assertSame(0, $this->db->table('articles')->countAllResults());
    }

    public function testDiasporaEditorsStillCannotPublishMissionActivities(): void
    {
        $editor = $this->editorIn($this->diaspora);
        $this->assertStringNotContainsString('value="mission_activity"', $this->actingAs($editor)->get('admin/content/articles/new')->getBody());
        $this->assertFalse(service('contentPolicy')->canEdit($editor, 'articles', ['category' => 'mission_activity', 'department_id' => $this->diaspora]));
    }

    public function testDiasporaCountryPagesOneCountryEach(): void
    {
        $editor  = $this->editorIn($this->diaspora);
        $country = $this->country('XA', 'Testland');

        $this->postAs($editor, 'admin/content/diaspora_countries', ['country_id' => (string) $country, 'title' => 'Testland diaspora', 'department_id' => (string) $this->diaspora]);
        $this->assertSame(1, $this->db->table('diaspora_country_pages')->countAllResults());

        $this->postAs($editor, 'admin/content/diaspora_countries', ['country_id' => (string) $country, 'title' => 'Second page', 'department_id' => (string) $this->diaspora])
            ->assertSee('This country already has a Diaspora by Country page.');
        $this->assertSame(1, $this->db->table('diaspora_country_pages')->countAllResults());
    }

    public function testSendingANewsletterIssue(): void
    {
        $editor = $this->editorIn($this->diaspora);
        $id     = $this->content('articles', ['category' => 'diaspora_newsletter', 'title' => 'Issue (test)', 'department_id' => $this->diaspora]);
        $this->db->table('newsletter_subscriptions')->insert(['email' => 'reader@example.invalid', 'status' => 'confirmed', 'source' => 'stay_informed', 'locale' => 'en']);

        $page = $this->actingAs($editor)->get('admin/newsletter')->getBody();
        $this->assertStringContainsString('Send to subscribers', $page);
        $this->assertStringNotContainsString('reader@example.invalid', $page, 'Subscriber addresses are not listed.');

        $this->postAs($this->editorIn($this->other), "admin/newsletter/{$id}/send")->assertStatus(403);
        $this->postAs($editor, "admin/newsletter/{$id}/send")->assertRedirectTo(site_url('admin/newsletter'));
        $this->assertSame(['reader@example.invalid'], array_column($this->db->table('mail_queue')->get()->getResultArray(), 'to_email'));

        $this->postAs($editor, "admin/newsletter/{$id}/send");
        $this->assertSame(1, $this->db->table('mail_queue')->countAllResults(), 'An issue is sent once.');
    }

    public function testNotificationRulesAreEditedByMinistryAdministrators(): void
    {
        $admin = $this->staff('ministryadmin');
        $this->postAs($admin, 'admin/content/notification_rules', [
            'submission_type' => 'inquiry', 'recipients' => "staff@example.invalid\nnot an address", 'subject' => 'New {reference}', 'is_active' => '1',
        ])->assertSee('Enter valid email addresses, one per line.');

        $this->postAs($admin, 'admin/content/notification_rules', [
            'submission_type' => 'inquiry', 'recipients' => 'staff@example.invalid', 'subject' => 'New {reference}', 'is_active' => '1',
        ]);
        $this->assertSame(1, $this->db->table('notification_rules')->countAllResults());

        $this->postAs($this->editorIn($this->diaspora), 'admin/content/notification_rules', [
            'submission_type' => 'inquiry', 'recipients' => 'x@example.invalid', 'subject' => 'x', 'is_active' => '1',
        ])->assertStatus(403);
    }

    private function editorIn(int ...$departments): User
    {
        $editor = $this->staff('editor');
        foreach ($departments as $department) {
            service('departmentAccess')->assign($editor, $department);
        }

        return $editor;
    }
}

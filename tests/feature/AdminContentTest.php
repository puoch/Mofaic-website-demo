<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\Support\StaffTestCase;

/**
 * Editing screens: direct publishing within scope, department limits,
 * mission activities, restore and mission coverage (GOV-02 to GOV-06).
 */
final class AdminContentTest extends StaffTestCase
{
    private int $deptA;
    private int $deptB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deptA = $this->department('a');
        $this->deptB = $this->department('b');
    }

    public function testEveryStaffRoleCanOpenThePublishingScreens(): void
    {
        foreach (['superadmin', 'ministryadmin', 'editor', 'readonly'] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get('admin/publishing')->assertOK();
            $this->actingAs($user)->get('admin/content/articles')->assertOK();
        }
    }

    public function testEditorCreatesAndPublishesInTheirDepartmentWithoutApproval(): void
    {
        $editor = $this->editorIn($this->deptA);

        $result = $this->postAs($editor, 'admin/content/articles', [
            'category' => 'news', 'title' => 'Editor news', 'department_id' => (string) $this->deptA, 'body' => 'Text',
        ]);
        $article = $this->db->table('articles')->where('title', 'Editor news')->get()->getRowArray();
        $this->assertNotNull($article);
        $result->assertRedirectTo(site_url('admin/content/articles/' . $article['id']) . '#media');
        $this->assertSame('draft', $article['status']);

        $this->postAs($editor, 'admin/content/articles/' . $article['id'] . '/publish');

        $this->assertSame('published', $this->db->table('articles')->where('id', $article['id'])->get()->getRow()->status);
        $this->assertSame(1, $this->db->table('audit_events')->where(['event' => 'content.published', 'actor_user_id' => $editor->id])->countAllResults());
    }

    public function testEditorCannotCreateInAnotherDepartment(): void
    {
        $result = $this->postAs($this->editorIn($this->deptA), 'admin/content/articles', [
            'category' => 'news', 'title' => 'Wrong department', 'department_id' => (string) $this->deptB,
        ]);

        $result->assertOK();
        $result->assertSee('Choose one of the listed options for Department.');
        $this->assertSame(0, $this->db->table('articles')->countAllResults());
    }

    public function testEditorCannotChangeOrPublishAnotherDepartmentsContent(): void
    {
        $id     = $this->content('articles', ['category' => 'news', 'title' => 'Dept B news', 'department_id' => $this->deptB], false);
        $editor = $this->editorIn($this->deptA);

        $this->postAs($editor, "admin/content/articles/{$id}/publish")->assertStatus(403);
        $this->postAs($editor, "admin/content/articles/{$id}", ['category' => 'news', 'title' => 'Changed', 'department_id' => (string) $this->deptA])->assertStatus(403);

        $row = $this->db->table('articles')->where('id', $id)->get()->getRow();
        $this->assertSame('draft', $row->status);
        $this->assertSame('Dept B news', $row->title);
    }

    public function testEditorCannotMoveContentOutOfTheirDepartments(): void
    {
        $id = $this->content('articles', ['category' => 'news', 'title' => 'Dept A news', 'department_id' => $this->deptA], false);

        $this->postAs($this->editorIn($this->deptA), "admin/content/articles/{$id}", ['category' => 'news', 'title' => 'Moved', 'department_id' => (string) $this->deptB])->assertOK();

        $this->assertSame((string) $this->deptA, (string) $this->db->table('articles')->where('id', $id)->get()->getRow()->department_id);
    }

    public function testEditorCannotPublishMissionActivities(): void
    {
        $mission = $this->content('missions', ['name' => 'Test mission', 'category' => 'abroad_embassy']);

        $this->postAs($this->editorIn($this->deptA), 'admin/content/articles', [
            'category' => 'mission_activity', 'title' => 'Activity', 'department_id' => (string) $this->deptA, 'mission_id' => (string) $mission,
        ])->assertSee('Choose one of the listed options for Category.');

        $this->assertSame(0, $this->db->table('articles')->countAllResults());
    }

    public function testMinistryAdministratorPublishesMissionActivities(): void
    {
        $admin   = $this->staff('ministryadmin');
        $mission = $this->content('missions', ['name' => 'Test mission', 'category' => 'abroad_embassy']);

        $this->postAs($admin, 'admin/content/articles', ['category' => 'mission_activity', 'title' => 'No mission chosen'])
            ->assertSee('Choose the mission this activity belongs to.');

        $this->postAs($admin, 'admin/content/articles', ['category' => 'mission_activity', 'title' => 'Activity', 'mission_id' => (string) $mission]);

        $this->assertSame((string) $mission, (string) $this->db->table('articles')->where('title', 'Activity')->get()->getRow()->mission_id);
    }

    public function testNewsGalleryAndMissionActivityFormsHaveAPlaceForPhotos(): void
    {
        $admin = $this->staff('superadmin');

        foreach (['articles', 'galleries'] as $type) {
            $new = $this->actingAs($admin)->get('admin/content/' . $type . '/new');
            $new->assertSee('data-testid="media-after-save"');
            $new->assertSee('Save first. The place to upload photos then appears here.');
        }

        $mission  = $this->content('missions', ['name' => 'Test mission', 'category' => 'abroad_embassy']);
        $activity = $this->content('articles', ['category' => 'mission_activity', 'title' => 'Test activity', 'mission_id' => $mission]);
        $album    = $this->content('galleries', ['title' => 'Test album']);

        $edit = $this->actingAs($admin)->get('admin/content/articles/' . $activity);
        $edit->assertSee('Photos and files');
        $edit->assertSee('data-testid="article-photos-help"');
        $edit->assertSee('name="file"', null);
        $edit->assertSee('enctype="multipart/form-data"', null);

        $this->actingAs($admin)->get('admin/content/galleries/' . $album)->assertSee('data-testid="album-photos-help"');
    }

    public function testReadOnlyStaffCanLookButNotChange(): void
    {
        $id       = $this->content('articles', ['category' => 'news', 'title' => 'Look only', 'department_id' => $this->deptA]);
        $readonly = $this->staff('readonly');

        $this->actingAs($readonly)->get("admin/content/articles/{$id}")->assertSee('You can view this item but not change it.');
        $this->postAs($readonly, "admin/content/articles/{$id}/unpublish")->assertStatus(403);
        $this->postAs($readonly, 'admin/content/articles', ['category' => 'news', 'title' => 'New'])->assertStatus(403);
    }

    public function testRestoreFromTheRevisionScreen(): void
    {
        $admin = $this->staff('ministryadmin');
        $id    = $this->content('articles', ['category' => 'news', 'title' => 'Version one', 'department_id' => $this->deptA]);
        service('content')->save('articles', $id, ['category' => 'news', 'title' => 'Version two', 'department_id' => $this->deptA], null);
        $first = service('content')->revisions('articles', $id)[1];

        $this->actingAs($admin)->get("admin/content/articles/{$id}/revisions")->assertSee('Version one');
        $this->postAs($admin, "admin/content/articles/{$id}/revisions/{$first['id']}/restore");

        $this->assertSame('Version one', $this->db->table('articles')->where('id', $id)->get()->getRow()->title);
    }

    public function testEditorCannotRestore(): void
    {
        $id = $this->content('articles', ['category' => 'news', 'title' => 'Version one', 'department_id' => $this->deptA]);
        service('content')->save('articles', $id, ['category' => 'news', 'title' => 'Version two', 'department_id' => $this->deptA], null);
        $first = service('content')->revisions('articles', $id)[1];

        $this->postAs($this->editorIn($this->deptA), "admin/content/articles/{$id}/revisions/{$first['id']}/restore")->assertStatus(403);
    }

    public function testMinistryAdministratorRecordsNonResidentCoverage(): void
    {
        $admin   = $this->staff('ministryadmin');
        $country = $this->country('XA', 'Testland Alpha');
        $mission = $this->content('missions', ['name' => 'Test mission', 'category' => 'abroad_embassy']);

        $this->postAs($admin, "admin/content/missions/{$mission}/coverage", ['country_id' => (string) $country, 'coverage' => 'non_resident']);

        $this->assertSame('non_resident', $this->db->table('mission_coverage')->where('mission_id', $mission)->get()->getRow()->coverage);
        $this->postAs($this->editorIn($this->deptA), "admin/content/missions/{$mission}/coverage", ['country_id' => (string) $country, 'coverage' => 'resident'])->assertStatus(403);
    }

    public function testInvalidValuesAreRejected(): void
    {
        $admin = $this->staff('ministryadmin');

        $this->postAs($admin, 'admin/content/quick_links', ['label' => 'Bad link', 'url' => 'javascript:alert(1)'])->assertSee('is-invalid', null);
        $this->postAs($admin, 'admin/content/countries', ['name' => 'Bad', 'iso_code' => 'ABC'])->assertSee('is-invalid', null);
        $this->postAs($admin, 'admin/content/pages', ['section' => 'services', 'title' => 'Links', 'external_links' => 'no address here'])
            ->assertSee('Write each link as: Label | https://address');

        $this->assertSame(0, $this->db->table('quick_links')->countAllResults());
        $this->assertSame(0, $this->db->table('countries')->countAllResults());
    }

    public function testUnknownContentTypeIsNotFound(): void
    {
        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->actingAs($this->staff('ministryadmin'))->get('admin/content/users');
    }

    private function editorIn(int $departmentId): \CodeIgniter\Shield\Entities\User
    {
        $editor = $this->staff('editor');
        service('departmentAccess')->assign($editor, $departmentId);

        return $editor;
    }
}

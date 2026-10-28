<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\Support\StaffTestCase;

/**
 * Who may change which content (GOV-02 to GOV-04, HOME-03, DIA-11).
 */
final class ContentPolicyTest extends StaffTestCase
{
    public function testEditorChangesContentOnlyInTheirDepartments(): void
    {
        $a      = $this->department('a');
        $b      = $this->department('b');
        $editor = $this->staff('editor');
        service('departmentAccess')->assign($editor, $a);
        $policy = service('contentPolicy');

        $this->assertTrue($policy->canEdit($editor, 'articles', ['category' => 'news', 'department_id' => $a]));
        $this->assertFalse($policy->canEdit($editor, 'articles', ['category' => 'news', 'department_id' => $b]));
        $this->assertFalse($policy->canEdit($editor, 'articles', ['category' => 'news', 'department_id' => null]));
        $this->assertTrue($policy->canEdit($editor, 'pages', ['department_id' => $a]));
    }

    public function testOnlyMinistryAdministratorsPublishMissionActivities(): void
    {
        $a      = $this->department('a');
        $editor = $this->staff('editor');
        service('departmentAccess')->assign($editor, $a);
        $policy = service('contentPolicy');
        $record = ['category' => 'mission_activity', 'department_id' => $a];

        $this->assertFalse($policy->canEdit($editor, 'articles', $record));
        $this->assertTrue($policy->canEdit($this->staff('ministryadmin'), 'articles', $record));
        $this->assertTrue($policy->canEdit($this->staff('superadmin'), 'articles', $record));
    }

    public function testDiasporaEditorsPublishInTheDiasporaDepartment(): void
    {
        $diaspora = $this->department('diaspora', true);
        $editor   = $this->staff('editor');
        service('departmentAccess')->assign($editor, $diaspora);

        $this->assertTrue(service('contentPolicy')->canEdit($editor, 'articles', ['category' => 'news', 'department_id' => $diaspora]));
    }

    public function testMissionRecordsAndSiteListsBelongToMinistryAdministrators(): void
    {
        $policy = service('contentPolicy');
        $editor = $this->staff('editor');
        $admin  = $this->staff('ministryadmin');

        foreach (['missions', 'countries', 'quick_links', 'social_accounts', 'org_units', 'site_blocks', 'tags'] as $type) {
            $this->assertFalse($policy->canEdit($editor, $type), "Editor must not change {$type}");
            $this->assertTrue($policy->canEdit($admin, $type), "Ministry Administrator must change {$type}");
        }
    }

    public function testReadOnlyStaffChangeNothing(): void
    {
        $a        = $this->department('a');
        $readonly = $this->staff('readonly');
        service('departmentAccess')->assign($readonly, $a);
        $policy = service('contentPolicy');

        foreach (array_keys(config('ContentTypes')->types) as $type) {
            $this->assertFalse($policy->canEdit($readonly, $type, ['department_id' => $a, 'category' => 'news']));
            $this->assertFalse($policy->canCreate($readonly, $type));
        }
        $this->assertTrue($policy->canView($readonly));
    }

    public function testRestoringNeedsTheRestorePermission(): void
    {
        $a      = $this->department('a');
        $editor = $this->staff('editor');
        service('departmentAccess')->assign($editor, $a);
        $record = ['category' => 'news', 'department_id' => $a];

        $this->assertFalse(service('contentPolicy')->canRestore($editor, 'articles', $record));
        $this->assertTrue(service('contentPolicy')->canRestore($this->staff('ministryadmin'), 'articles', $record));
    }

    public function testEditorWithoutDepartmentsCannotStartContent(): void
    {
        $this->assertFalse(service('contentPolicy')->canCreate($this->staff('editor'), 'articles'));
    }

    public function testFixedHomePageNoticesCannotBeAdded(): void
    {
        $this->assertFalse(service('contentPolicy')->canCreate($this->staff('ministryadmin'), 'site_blocks'));
    }
}

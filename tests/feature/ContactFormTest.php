<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\StructureSeeder;
use Tests\Support\StaffTestCase;

/**
 * CONTACTS: public inquiry form (CON-01 to CON-04, SEC-02, EXC-01).
 */
final class ContactFormTest extends StaffTestCase
{
    private const VALID = ['name' => 'Test Visitor', 'email' => 'visitor@example.invalid', 'phone' => '', 'subject' => 'A question', 'message' => 'Hello Ministry.'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StructureSeeder::class);
    }

    public function testFormStaysClosedUntilThePrivacyNoticeIsPublished(): void
    {
        $page = $this->get('en/contacts');
        $page->assertSee('data-testid="form-closed"', null);
        $this->assertStringNotContainsString('data-testid="inquiry-form"', $page->getBody());

        $this->submitForm('en/contacts', 'inquiry', self::VALID)->assertOK();
        $this->assertSame(0, $this->db->table('inquiries')->countAllResults(), 'A closed form accepts nothing.');

        $this->block('privacy_notice', 'Approved privacy notice (test).');
        $page = $this->get('en/contacts')->getBody();
        $this->assertStringContainsString('data-testid="inquiry-form"', $page);
        $this->assertStringContainsString('Approved privacy notice (test).', $page);
    }

    public function testAValidInquiryIsStoredAndTheVisitorGetsAReference(): void
    {
        $this->openForms();

        $result = $this->submitForm('en/contacts', 'inquiry', self::VALID);
        $result->assertRedirectTo(site_url('en/contacts/thanks'));

        $row = $this->db->table('inquiries')->get()->getRowArray();
        $this->assertSame('Test Visitor', $row['name']);
        $this->assertNull($row['phone']);
        $this->assertSame('new', $row['status']);

        $this->withSession(['reference' => $row['reference']])->get('en/contacts/thanks')->assertSee($row['reference']);
        $this->withSession([])->get('en/contacts/thanks')->assertRedirectTo(site_url('en/contacts'));
    }

    public function testRequiredFieldsAndFormatsAreChecked(): void
    {
        $this->openForms();

        $result = $this->submitForm('en/contacts', 'inquiry', ['name' => '', 'email' => 'not-an-email', 'message' => '', 'phone' => 'call me']);
        $result->assertOK();
        $html = $result->getBody();
        $this->assertStringContainsString('data-testid="form-errors"', $html);
        foreach (['field-name', 'field-email', 'field-message', 'field-phone'] as $id) {
            $this->assertMatchesRegularExpression('/id="' . $id . '"[^>]*aria-invalid="true"/', $html);
        }
        $this->assertStringContainsString('value="call me"', $html, 'Entries are kept when the form is shown again.');
        $this->assertSame(0, $this->db->table('inquiries')->countAllResults());
    }

    public function testSpamChecksStopAutomatedSubmissions(): void
    {
        $this->openForms();

        $this->submitForm('en/contacts', 'inquiry', ['website_url' => 'http://spam.example.invalid'] + self::VALID)->assertSee(lang('Forms.notSent'));
        $this->submitForm('en/contacts', 'inquiry', self::VALID, 0)->assertSee(lang('Forms.tooFast'));
        $this->assertSame(0, $this->db->table('inquiries')->countAllResults());
    }

    public function testTheFormHasNoFileUploadsOrApplicationFields(): void
    {
        $this->openForms();
        $html = $this->get('en/contacts')->getBody();

        $this->assertStringNotContainsString('type="file"', $html);
        $this->assertStringNotContainsString('multipart/form-data', $html);
        foreach (['passport', 'visa_application', 'national_id', 'bank'] as $field) {
            $this->assertStringNotContainsString('name="' . $field, $html);
        }
    }

    public function testInquiriesAppearOnlyInThePublicInquiriesInbox(): void
    {
        $this->openForms();
        $this->submitForm('en/contacts', 'inquiry', self::VALID);
        $id = (int) $this->db->table('inquiries')->get()->getRow()->id;

        $admin = $this->staff('ministryadmin');
        $this->actingAs($admin)->get('admin/inquiries')->assertSee('INQ-');
        $this->actingAs($admin)->get("admin/inquiries/inquiry/{$id}")->assertSee('Hello Ministry.');

        foreach (['editor', 'readonly', 'superadmin'] as $role) {
            $this->actingAs($this->staff($role))->get('admin/inquiries')->assertStatus(403);
        }

        $this->expectException(\CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->actingAs($admin)->get("admin/diaspora/inquiry/{$id}");
    }
}

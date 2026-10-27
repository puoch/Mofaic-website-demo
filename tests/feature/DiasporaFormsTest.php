<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\Seeds\StructureSeeder;
use Tests\Support\StaffTestCase;

/**
 * SSD DIASPORA public pages: voluntary registration and mission choice,
 * contributions, newsletter opt-in, Stay Informed and Diaspora by Country
 * (DIA-01 to DIA-12, EXC-02).
 */
final class DiasporaFormsTest extends StaffTestCase
{
    private int $xa;
    private int $xc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StructureSeeder::class);
        $this->xa = $this->country('XA', 'Testland');
        $this->xc = $this->country('XC', 'Nomissionland');
    }

    public function testTheFourAreasAreLinked(): void
    {
        $html = $this->get('en/diaspora')->getBody();
        foreach (['diaspora/register', 'diaspora/contribute', 'diaspora/stay-informed', 'diaspora/countries'] as $path) {
            $this->assertStringContainsString('href="' . site_url('en/' . $path) . '"', $html);
        }
    }

    public function testRegistrationNeedsTheApprovedStatementAndPrivacyNotice(): void
    {
        $this->assertStringContainsString('data-testid="form-closed"', $this->get('en/diaspora/register')->getBody());

        $this->block('privacy_notice', 'Privacy (test)');
        $this->assertStringContainsString('data-testid="form-closed"', $this->get('en/diaspora/register')->getBody(), 'The voluntary statement is also required.');

        $this->block('diaspora_registration_statement', 'Registration is voluntary (test).');
        $html = $this->get('en/diaspora/register')->getBody();
        $this->assertStringContainsString('Registration is voluntary (test).', $html);
        $this->assertStringContainsString('data-testid="registration-form"', $html);
    }

    public function testTheRegistrationFormAsksForNoIdentityFinancialOrFileData(): void
    {
        $this->openForms();
        $html = $this->get('en/diaspora/register')->getBody();

        $this->assertStringNotContainsString('type="file"', $html);
        $this->assertStringNotContainsString('multipart/form-data', $html);
        foreach (['passport', 'national_id', 'bank', 'iban', 'card'] as $field) {
            $this->assertDoesNotMatchRegularExpression('/name="[^"]*' . $field . '/i', $html);
        }
        $this->assertDoesNotMatchRegularExpression('/name="newsletter"[^>]*checked/', $html, 'The newsletter box starts unticked.');
    }

    public function testCityCountryAndAContactMethodAreRequired(): void
    {
        $this->openForms();

        $html = $this->submitForm('en/diaspora/register', 'registration', ['name' => 'Test Person'])->getBody();
        foreach (['field-city', 'field-country_id', 'field-email'] as $id) {
            $this->assertMatchesRegularExpression('/id="' . $id . '"[^>]*aria-invalid="true"/', $html);
        }
        $this->assertStringContainsString(lang('Forms.emailOrPhone'), $html);
        $this->assertSame(0, $this->db->table('diaspora_registrations')->countAllResults());

        $this->submitForm('en/diaspora/register', 'registration', $this->registration(['email' => '', 'phone' => '+000 123 456']))
            ->assertRedirectTo(site_url('en/diaspora/register/missions'));
        $this->assertSame(1, $this->db->table('diaspora_registrations')->countAllResults(), 'A telephone number alone is enough.');
    }

    public function testOnlyListedCountriesAreAccepted(): void
    {
        $this->openForms();
        $this->submitForm('en/diaspora/register', 'registration', $this->registration(['country_id' => '99999']))
            ->assertSee(lang('Forms.chooseListed', [lang('Forms.countryOfResidence')]));
    }

    public function testEveryServingMissionIsListedAndNoneIsChosenForTheVisitor(): void
    {
        $this->openForms();
        [$resident, $nonResident, $elsewhere] = $this->missions();

        $this->submitForm('en/diaspora/register', 'registration', $this->registration());
        $id = (int) $this->db->table('diaspora_registrations')->get()->getRow()->id;

        $html = $this->withSession(['diaspora_registration' => $id])->get('en/diaspora/register/missions')->getBody();
        $this->assertStringContainsString('Resident Embassy (test)', $html);
        $this->assertStringContainsString('Covering Embassy (test)', $html);
        $this->assertStringContainsString(lang('Forms.nonResident'), $html);
        $this->assertStringNotContainsString('Elsewhere Embassy (test)', $html);
        $this->assertStringNotContainsString('checked', $html);
        $this->assertNull($this->db->table('diaspora_registrations')->get()->getRow()->chosen_mission_id);

        // A mission that does not serve the country cannot be chosen.
        $this->withSession(['diaspora_registration' => $id])->post('en/diaspora/register/missions', [csrf_token() => csrf_hash(), 'mission_id' => (string) $elsewhere]);
        $this->assertNull($this->db->table('diaspora_registrations')->get()->getRow()->chosen_mission_id);

        $this->withSession(['diaspora_registration' => $id])->post('en/diaspora/register/missions', [csrf_token() => csrf_hash(), 'mission_id' => (string) $nonResident])
            ->assertRedirectTo(site_url('en/diaspora/register/confirmation'));
        $this->assertSame($nonResident, (int) $this->db->table('diaspora_registrations')->get()->getRow()->chosen_mission_id);

        $confirmation = $this->withSession(['diaspora_registration' => $id])->get('en/diaspora/register/confirmation')->getBody();
        $this->assertStringContainsString('Covering Embassy (test)', $confirmation);
        $this->assertStringContainsString('mailto:covering@example.invalid', $confirmation);
        $this->assertStringContainsString(lang('Forms.confirmationIntro'), $confirmation);
    }

    public function testRegistrationDetailsAreNotSentToTheMission(): void
    {
        $this->openForms();
        [, $nonResident] = $this->missions();
        $this->rule('registration', 'diaspora-staff@example.invalid');

        $this->submitForm('en/diaspora/register', 'registration', $this->registration());
        $id = (int) $this->db->table('diaspora_registrations')->get()->getRow()->id;
        $this->withSession(['diaspora_registration' => $id])->post('en/diaspora/register/missions', [csrf_token() => csrf_hash(), 'mission_id' => (string) $nonResident]);

        $this->assertSame(['diaspora-staff@example.invalid'], array_column($this->db->table('mail_queue')->get()->getResultArray(), 'to_email'));
        $this->assertStringNotContainsString('Test Person', (string) $this->db->table('mail_queue')->get()->getRow()->body);
    }

    public function testWhenNoMissionServesTheCountryTheRegistrationIsStillSaved(): void
    {
        $this->openForms();
        $this->block('headquarters_contact', 'Headquarters contact (test).');

        $this->submitForm('en/diaspora/register', 'registration', $this->registration(['country_id' => (string) $this->xc]));
        $row  = $this->db->table('diaspora_registrations')->get()->getRowArray();
        $html = $this->withSession(['diaspora_registration' => (int) $row['id']])->get('en/diaspora/register/missions')->getBody();

        $this->assertStringContainsString('data-testid="no-mission"', $html);
        $this->assertStringContainsString('Headquarters contact (test).', $html);
        $this->assertStringContainsString($row['reference'], $html);
    }

    public function testTheNewsletterIsASeparateOptIn(): void
    {
        $this->openForms();

        $this->submitForm('en/diaspora/register', 'registration', $this->registration());
        $this->assertSame(0, $this->db->table('newsletter_subscriptions')->countAllResults(), 'Registering without ticking does not subscribe.');

        $this->submitForm('en/diaspora/register', 'registration', $this->registration(['email' => 'reader@example.invalid', 'newsletter' => '1']));
        $subscription = $this->db->table('newsletter_subscriptions')->get()->getRowArray();
        $this->assertSame('pending', $subscription['status']);
        $this->assertSame('registration', $subscription['source']);

        $this->submitForm('en/diaspora/register', 'registration', $this->registration(['email' => '', 'phone' => '+000 1', 'newsletter' => '1']))
            ->assertSee(lang('Forms.newsletterNeedsEmail'));
    }

    public function testAnonymousContributionsWithACategory(): void
    {
        $this->openForms();
        $html = $this->get('en/diaspora/contribute')->getBody();
        foreach (config('Submissions')->contributionCategories as $label) {
            $this->assertStringContainsString(esc($label), $html);
        }
        $this->assertStringNotContainsString('type="file"', $html);
        $this->assertDoesNotMatchRegularExpression('/name="(amount|card|payment)/', $html);

        $this->submitForm('en/diaspora/contribute', 'contribution', ['category' => 'ideas', 'message' => 'An idea.'])
            ->assertRedirectTo(site_url('en/diaspora/contribute/thanks'));
        $row = $this->db->table('diaspora_contributions')->get()->getRowArray();
        $this->assertSame('ideas', $row['category']);
        $this->assertNull($row['name']);
        $this->assertNull($row['email']);

        $this->submitForm('en/diaspora/contribute', 'contribution', ['category' => 'payments', 'message' => 'x'])->assertSee('Category');
        $this->assertSame(1, $this->db->table('diaspora_contributions')->countAllResults());
    }

    public function testStayInformedSubscriptionNeedsConsent(): void
    {
        $this->openForms();

        $this->submitForm('en/diaspora/stay-informed/subscribe', 'newsletter', ['email' => 'reader@example.invalid'])->assertSee(lang('Forms.subscribeConsentRequired'));
        $this->assertSame(0, $this->db->table('newsletter_subscriptions')->countAllResults());

        $this->submitForm('en/diaspora/stay-informed/subscribe', 'newsletter', ['email' => 'reader@example.invalid', 'consent' => '1'])
            ->assertRedirectTo(site_url('en/diaspora/stay-informed/subscribed'));
        $this->assertSame(1, $this->db->table('newsletter_subscriptions')->countAllResults());
    }

    public function testConfirmAndUnsubscribePages(): void
    {
        service('newsletter')->subscribe('reader@example.invalid', 'stay_informed', 'en');
        preg_match('#/confirm/([0-9a-f]{64})#', (string) $this->db->table('mail_queue')->get()->getRow()->body, $m);

        $this->get('en/diaspora/newsletter/confirm/' . $m[1])->assertSee(lang('Forms.confirmedHeading'));
        $row = $this->db->table('newsletter_subscriptions')->get()->getRowArray();
        $this->assertSame('confirmed', $row['status']);

        $path = 'en/diaspora/newsletter/unsubscribe/' . $row['id'] . '/' . \App\Libraries\Newsletter::unsubscribeSignature((int) $row['id'], $row['email']);
        $this->get($path)->assertSee(lang('Forms.unsubscribeButton'));
        $this->assertSame('confirmed', $this->db->table('newsletter_subscriptions')->get()->getRow()->status, 'Opening the link alone does not unsubscribe.');

        $this->post($path, [csrf_token() => csrf_hash()])->assertSee(lang('Forms.unsubscribedText'));
        $this->assertSame('unsubscribed', $this->db->table('newsletter_subscriptions')->get()->getRow()->status);
    }

    public function testDiasporaUpdatesAppearUnderStayInformedOnly(): void
    {
        $dept = $this->department('diaspora', true);
        $this->content('articles', ['category' => 'diaspora_event', 'title' => 'Diaspora event (test)', 'department_id' => $dept]);
        $this->content('articles', ['category' => 'news', 'title' => 'Ordinary news (test)']);

        $html = $this->get('en/diaspora/stay-informed')->getBody();
        $this->assertStringContainsString('Diaspora event (test)', $html);
        $this->assertStringNotContainsString('Ordinary news (test)', $html);
        $this->assertStringNotContainsString('Diaspora event (test)', $this->get('en/news-press/news')->getBody());
        $this->assertStringNotContainsString('Diaspora event (test)', $this->get('en/diaspora/stay-informed?type=announcements')->getBody());

        $slug = $this->db->table('articles')->where('category', 'diaspora_event')->get()->getRow()->slug;
        $this->get('en/diaspora/stay-informed/' . $slug)->assertSee('Diaspora event (test)');
    }

    public function testDiasporaByCountryShowsPublishedPagesAndServingMissions(): void
    {
        $dept = $this->department('diaspora', true);
        $this->missions();
        $this->content('diaspora_countries', ['country_id' => $this->xa, 'title' => 'Diaspora in Testland', 'department_id' => $dept]);
        $this->content('diaspora_countries', ['country_id' => $this->xc, 'title' => 'Draft page', 'department_id' => $dept], false);

        $list = $this->get('en/diaspora/countries')->getBody();
        $this->assertStringContainsString('Testland', $list);
        $this->assertStringNotContainsString('Nomissionland', $list);

        $page = $this->get('en/diaspora/countries/diaspora-in-testland')->getBody();
        $this->assertStringContainsString('Resident Embassy (test)', $page);
    }

    /**
     * @return list<int> resident, non-resident (both serve XA), elsewhere
     */
    private function missions(): array
    {
        $xb = $this->country('XB', 'Elsewhere');
        $ids = [];
        foreach ([
            ['Resident Embassy (test)', $this->xa, 'resident', $this->xa, 'resident@example.invalid'],
            ['Covering Embassy (test)', $xb, 'non_resident', $this->xa, 'covering@example.invalid'],
            ['Elsewhere Embassy (test)', $xb, 'resident', $xb, 'elsewhere@example.invalid'],
        ] as [$name, $location, $coverage, $serves, $email]) {
            $id = $this->content('missions', ['name' => $name, 'category' => 'abroad_embassy', 'location_country_id' => $location, 'email' => $email]);
            $this->db->table('mission_coverage')->insert(['mission_id' => $id, 'country_id' => $serves, 'coverage' => $coverage]);
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * @param array<string, string> $overrides
     *
     * @return array<string, string>
     */
    private function registration(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Test Person', 'city' => 'Test City', 'country_id' => (string) $this->xa,
            'email' => 'person@example.invalid', 'phone' => '', 'inquiry_nature' => '', 'consular_help' => '',
        ];
    }

    private function rule(string $type, string $recipients): void
    {
        $this->db->table('notification_rules')->insert(['submission_type' => $type, 'recipients' => $recipients, 'subject' => 'New {reference}', 'is_active' => 1]);
    }
}

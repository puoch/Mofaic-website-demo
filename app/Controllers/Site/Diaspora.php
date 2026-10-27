<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use CodeIgniter\HTTP\RedirectResponse;

/**
 * SSD DIASPORA (brief section 2): Register, Contribute, Stay Informed and
 * Diaspora by Country.
 *
 * Registration is voluntary. Afterwards the visitor sees every mission
 * serving their country of residence and chooses one; nothing is chosen for
 * them and their details are not sent to the mission. The forms ask for no
 * passport, national ID, financial or banking details and accept no files.
 */
class Diaspora extends FormController
{
    private const SESSION_KEY = 'diaspora_registration';

    private const STAY_INFORMED = [
        'announcements' => ['diaspora_announcement', 'Forms.announcements'],
        'events'        => ['diaspora_event', 'Forms.events'],
        'newsletters'   => ['diaspora_newsletter', 'Forms.newsletters'],
    ];

    public function index(): string
    {
        return view('site/diaspora/index', [
            'latest' => $this->updates()->limit(3)->get()->getResultArray(),
        ]);
    }

    // Register ---------------------------------------------------------

    public function register(): string
    {
        return $this->registrationForm([], []);
    }

    public function registerSubmit(): RedirectResponse|string
    {
        if (! $this->registrationOpen()) {
            return $this->registrationForm([], []);
        }

        $values     = $this->posted(['name', 'address', 'city', 'locality', 'country_id', 'email', 'phone', 'inquiry_nature', 'consular_help']);
        $newsletter = $this->request->getPost('newsletter') !== null;

        if (($error = $this->guardError('registration')) !== null) {
            return $this->registrationForm($values, ['form' => $error], $newsletter);
        }

        $errors = $this->validateValues($values, [
            'name'           => ['label' => lang('Forms.fullName'), 'rules' => (config('Submissions')->registrationNameRequired ? 'required' : 'permit_empty') . '|max_length[190]'],
            'address'        => ['label' => lang('Forms.address'), 'rules' => 'permit_empty|max_length[500]'],
            'city'           => ['label' => lang('Forms.city'), 'rules' => 'required|max_length[190]'],
            'locality'       => ['label' => lang('Forms.locality'), 'rules' => 'permit_empty|max_length[190]'],
            'country_id'     => ['label' => lang('Forms.countryOfResidence'), 'rules' => 'required|is_natural_no_zero'],
            'email'          => ['label' => lang('Forms.email'), 'rules' => 'permit_empty|valid_email|max_length[190]'],
            'phone'          => ['label' => lang('Forms.phone'), 'rules' => self::phoneRule()],
            'inquiry_nature' => ['label' => lang('Forms.inquiryNature'), 'rules' => 'permit_empty|max_length[2000]'],
            'consular_help'  => ['label' => lang('Forms.consularHelp'), 'rules' => 'permit_empty|max_length[2000]'],
        ]);

        if (! isset($errors['country_id']) && ! array_key_exists($values['country_id'], $this->countryOptions())) {
            $errors['country_id'] = lang('Forms.chooseListed', [lang('Forms.countryOfResidence')]);
        }

        if ($values['email'] === '' && $values['phone'] === '' && ! isset($errors['email'], $errors['phone'])) {
            $errors['email'] = lang('Forms.emailOrPhone');
        }

        if ($newsletter && $values['email'] === '' && ! isset($errors['email'])) {
            $errors['email'] = lang('Forms.newsletterNeedsEmail');
        }

        if ($errors !== []) {
            return $this->registrationForm($values, $errors, $newsletter);
        }

        $data               = $this->nullable($values);
        $data['country_id'] = (int) $values['country_id'];
        $saved              = service('submissions')->create('registration', $data, $this->locale());

        // A separate subscription record, only when the visitor ticked the box (DIA-10).
        if ($newsletter) {
            service('newsletter')->subscribe($values['email'], 'registration', $this->locale());
        }

        session()->set(self::SESSION_KEY, $saved['id']);

        return $this->toLocale('diaspora/register/missions');
    }

    public function missions(): RedirectResponse|string
    {
        $registration = $this->currentRegistration();

        if ($registration === null) {
            return $this->toLocale('diaspora/register');
        }

        if ($registration['chosen_mission_id'] !== null) {
            return $this->toLocale('diaspora/register/confirmation');
        }

        $country = db_connect()->table('countries')->where('id', $registration['country_id'])->get()->getRowArray();

        return view('site/diaspora/missions', [
            'registration' => $registration,
            'country'      => $country,
            'missions'     => service('missionDirectory')->servingCountry((int) $registration['country_id'], $this->locale()),
            'hqHtml'       => $this->blockHtml('headquarters_contact'),
        ]);
    }

    public function chooseMission(): RedirectResponse
    {
        $registration = $this->currentRegistration();

        if ($registration === null) {
            return $this->toLocale('diaspora/register');
        }

        $missionId = (int) $this->request->getPost('mission_id');
        $serving   = array_map('intval', array_column(service('missionDirectory')->servingCountry((int) $registration['country_id'], $this->locale()), 'id'));

        // Only one of the listed missions can be chosen, and only once.
        if ($registration['chosen_mission_id'] === null && in_array($missionId, $serving, true)) {
            db_connect()->table('diaspora_registrations')->where('id', $registration['id'])->update([
                'chosen_mission_id' => $missionId,
                'mission_chosen_at' => date('Y-m-d H:i:s'),
            ]);
            service('auditLog')->record('submission.mission-chosen', null, 'registration', (int) $registration['id'], ['mission_id' => $missionId]);

            return $this->toLocale('diaspora/register/confirmation');
        }

        return $this->toLocale('diaspora/register/missions');
    }

    public function confirmation(): RedirectResponse|string
    {
        $registration = $this->currentRegistration();

        if ($registration === null || $registration['chosen_mission_id'] === null) {
            return $this->toLocale('diaspora/register');
        }

        $mission = service('missionDirectory')->profileById((int) $registration['chosen_mission_id'], $this->locale());

        return view('site/diaspora/confirmation', ['registration' => $registration, 'mission' => $mission]);
    }

    // Contribute -------------------------------------------------------

    public function contribute(): string
    {
        return $this->contributionForm([], []);
    }

    public function contributeSubmit(): RedirectResponse|string
    {
        if (! $this->formOpen('contribution')) {
            return $this->contributionForm([], []);
        }

        $values = $this->posted(['category', 'message', 'name', 'email', 'phone']);

        if (($error = $this->guardError('contribution')) !== null) {
            return $this->contributionForm($values, ['form' => $error]);
        }

        $categories = config('Submissions')->contributionCategories;
        $errors     = $this->validateValues($values, [
            'category' => ['label' => lang('Forms.category'), 'rules' => 'required|in_list[' . implode(',', array_keys($categories)) . ']'],
            'message'  => ['label' => lang('Forms.message'), 'rules' => 'required|max_length[5000]'],
            'name'     => ['label' => lang('Forms.name'), 'rules' => 'permit_empty|max_length[190]'],
            'email'    => ['label' => lang('Forms.email'), 'rules' => 'permit_empty|valid_email|max_length[190]'],
            'phone'    => ['label' => lang('Forms.phone'), 'rules' => self::phoneRule()],
        ]);

        if ($errors !== []) {
            return $this->contributionForm($values, $errors);
        }

        $saved = service('submissions')->create('contribution', $this->nullable($values), $this->locale());

        return $this->toLocale('diaspora/contribute/thanks')->with('reference', $saved['reference']);
    }

    public function contributeThanks(): RedirectResponse|string
    {
        $reference = session('reference');

        if (! is_string($reference)) {
            return $this->toLocale('diaspora/contribute');
        }

        return view('site/thanks', [
            'heading'   => lang('Forms.contribute'),
            'crumbs'    => [[lang('Site.nav.diaspora'), locale_url('diaspora')], [lang('Forms.contribute')]],
            'message'   => lang('Forms.contributeThanks'),
            'reference' => $reference,
        ]);
    }

    // Stay Informed ----------------------------------------------------

    public function stayInformed(): string
    {
        $filter  = (string) $this->request->getGet('type');
        $builder = $this->updates();

        if (isset(self::STAY_INFORMED[$filter])) {
            $builder->where('category', self::STAY_INFORMED[$filter][0]);
        } else {
            $filter = '';
        }

        return view('site/diaspora/stay_informed', [
            'articles'    => $builder->limit(30)->get()->getResultArray(),
            'filter'      => $filter,
            'filters'     => array_map(static fn (array $f): string => lang($f[1]), self::STAY_INFORMED),
            'formOpen'    => $this->formOpen('newsletter'),
            'privacyHtml' => $this->formOpen('newsletter') ? $this->blockHtml('privacy_notice') : '',
            'errors'      => [],
            'values'      => [],
        ]);
    }

    public function article(string $slug): string
    {
        $article = $this->requireRow($this->updates()->where('slug', $slug)->get()->getRowArray());

        return view('site/diaspora/article', [
            'article' => $article,
            'media'   => service('media')->forOwner('articles', (int) $article['id']),
            'bodyHtml'=> service('markdown')->toHtml($article['body']),
        ]);
    }

    public function subscribe(): RedirectResponse|string
    {
        if (! $this->formOpen('newsletter')) {
            return $this->stayInformed();
        }

        $values = $this->posted(['email']);
        $errors = [];

        if (($error = $this->guardError('newsletter')) !== null) {
            $errors['form'] = $error;
        } else {
            $errors = $this->validateValues($values, ['email' => ['label' => lang('Forms.email'), 'rules' => 'required|valid_email|max_length[190]']]);
            if ($this->request->getPost('consent') === null) {
                $errors['consent'] = lang('Forms.subscribeConsentRequired');
            }
        }

        if ($errors !== []) {
            return view('site/diaspora/stay_informed', [
                'articles'    => $this->updates()->limit(30)->get()->getResultArray(),
                'filter'      => '',
                'filters'     => array_map(static fn (array $f): string => lang($f[1]), self::STAY_INFORMED),
                'formOpen'    => true,
                'privacyHtml' => $this->blockHtml('privacy_notice'),
                'errors'      => $errors,
                'values'      => $values,
            ]);
        }

        service('newsletter')->subscribe($values['email'], 'stay_informed', $this->locale());

        return $this->toLocale('diaspora/stay-informed/subscribed');
    }

    public function subscribed(): string
    {
        return view('site/diaspora/message', ['heading' => lang('Forms.subscribeHeading'), 'message' => lang('Forms.subscribeThanks')]);
    }

    public function confirmSubscription(string $token): string
    {
        $ok = service('newsletter')->confirm($token);

        return view('site/diaspora/message', [
            'heading' => $ok ? lang('Forms.confirmedHeading') : lang('Forms.subscribeHeading'),
            'message' => $ok ? lang('Forms.confirmedText') : lang('Forms.confirmFailed'),
        ]);
    }

    /**
     * Shows a button rather than unsubscribing on GET, so that link checkers
     * in mail systems do not unsubscribe people.
     */
    public function unsubscribe(int $id, string $signature): string
    {
        $valid = service('newsletter')->findForUnsubscribe($id, $signature) !== null;

        return view('site/diaspora/unsubscribe', ['valid' => $valid, 'id' => $id, 'signature' => $signature]);
    }

    public function unsubscribeSubmit(int $id, string $signature): string
    {
        $ok = service('newsletter')->unsubscribe($id, $signature);

        return view('site/diaspora/message', [
            'heading' => lang('Forms.unsubscribeHeading'),
            'message' => $ok ? lang('Forms.unsubscribedText') : lang('Forms.unsubscribeFailed'),
        ]);
    }

    // Diaspora by Country ----------------------------------------------

    public function countries(): string
    {
        $pages = $this->published('diaspora_country_pages')
            ->select('diaspora_country_pages.*, countries.name AS country_name')
            ->join('countries', 'countries.id = diaspora_country_pages.country_id')
            ->orderBy('countries.name')
            ->get()
            ->getResultArray();

        return view('site/diaspora/countries', ['pages' => $pages]);
    }

    public function country(string $slug): string
    {
        $page = $this->requireRow($this->published('diaspora_country_pages')->where('slug', $slug)->get()->getRowArray());
        $country = db_connect()->table('countries')->where('id', $page['country_id'])->get()->getRowArray();

        return view('site/diaspora/country', [
            'page'     => $page,
            'country'  => $country,
            'bodyHtml' => service('markdown')->toHtml($page['body']),
            'media'    => service('media')->forOwner('diaspora_countries', (int) $page['id']),
            'missions' => service('missionDirectory')->servingCountry((int) $page['country_id'], $this->locale()),
        ]);
    }

    // Helpers ----------------------------------------------------------

    private function updates(): \CodeIgniter\Database\BaseBuilder
    {
        return $this->published('articles')
            ->whereIn('category', config('ContentTypes')->diasporaArticleCategories)
            ->orderBy('published_at', 'DESC');
    }

    private function registrationOpen(): bool
    {
        return $this->formOpen('registration') && $this->countryOptions() !== [];
    }

    /**
     * @return array<string, string> id => name
     */
    private function countryOptions(): array
    {
        return array_column(db_connect()->table('countries')->orderBy('name')->get()->getResultArray(), 'name', 'id');
    }

    /**
     * The registration made in this browser session, if any.
     *
     * @return array<string, mixed>|null
     */
    private function currentRegistration(): ?array
    {
        $id = session(self::SESSION_KEY);

        return is_int($id) ? service('submissions')->find('registration', $id) : null;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function registrationForm(array $values, array $errors, bool $newsletter = false): string
    {
        $open = $this->registrationOpen();

        return view('site/diaspora/register', [
            'formOpen'      => $open,
            'statementHtml' => $open ? $this->blockHtml('diaspora_registration_statement') : '',
            'privacyHtml'   => $open ? $this->blockHtml('privacy_notice') : '',
            'countries'     => $open ? $this->countryOptions() : [],
            'nameRequired'  => config('Submissions')->registrationNameRequired,
            'values'        => $values,
            'errors'        => $errors,
            'newsletter'    => $newsletter,
        ]);
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function contributionForm(array $values, array $errors): string
    {
        $open = $this->formOpen('contribution');

        return view('site/diaspora/contribute', [
            'formOpen'    => $open,
            'privacyHtml' => $open ? $this->blockHtml('privacy_notice') : '',
            'categories'  => config('Submissions')->contributionCategories,
            'values'      => $values,
            'errors'      => $errors,
        ]);
    }
}

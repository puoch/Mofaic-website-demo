<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\ContentPolicy;
use App\Libraries\ContentService;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;
use Config\ContentTypes;
use Config\Media;
use InvalidArgumentException;

/**
 * Editing screens for every type in Config\ContentTypes.
 *
 * Every staff member can look; changing anything is checked against
 * ContentPolicy on the record as it is and as it will be after the change.
 * Authorized editors publish directly (no second approval, brief section 3).
 */
class Content extends BaseController
{
    private const PER_PAGE = 25;

    private ContentService $content;
    private ContentPolicy $policy;
    private ContentTypes $types;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, ResponseInterface $response, \Psr\Log\LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        helper(['form']);
        $this->content = service('content');
        $this->policy  = service('contentPolicy');
        $this->types   = config(ContentTypes::class);
    }

    public function index(string $type): string
    {
        $definition = $this->definition($type);
        $builder    = db_connect()->table($definition['table']);

        if (! empty($definition['localized'])) {
            $builder->where('locale', $this->locale());
        }

        $q = trim((string) $this->request->getGet('q'));
        if ($q !== '') {
            $builder->like($definition['titleField'], mb_substr($q, 0, 100));
        }

        $status = (string) $this->request->getGet('status');
        if (! empty($definition['status']) && in_array($status, ['draft', 'published'], true)) {
            $builder->where('status', $status);
        }

        $total = (clone $builder)->countAllResults();
        $page  = max(1, (int) $this->request->getGet('page'));
        $rows  = $builder
            ->orderBy(in_array('updated_at', db_connect()->getFieldNames($definition['table']), true) ? 'updated_at' : 'id', 'DESC')
            ->limit(self::PER_PAGE, ($page - 1) * self::PER_PAGE)
            ->get()
            ->getResultArray();

        $user = $this->user();

        return view('admin/content/index', [
            'type'       => $type,
            'definition' => $definition,
            'rows'       => $rows,
            'editable'   => array_map(fn (array $row): bool => $this->policy->canEdit($user, $type, $row), $rows),
            'canCreate'  => $this->policy->canCreate($user, $type),
            'q'          => $q,
            'status'     => $status,
            'page'       => $page,
            'pages'      => (int) ceil($total / self::PER_PAGE),
            'labels'     => $this->listLabels($type),
        ]);
    }

    public function new(string $type): ResponseInterface|string
    {
        $this->definition($type);

        if (! $this->policy->canCreate($this->user(), $type)) {
            return $this->forbidden();
        }

        return $this->form($type, null, $this->defaults($type));
    }

    public function create(string $type): ResponseInterface|RedirectResponse|string
    {
        $this->definition($type);
        $user = $this->user();

        if (! $this->policy->canCreate($user, $type)) {
            return $this->forbidden();
        }

        $input  = $this->input($type);
        $errors = $this->validateInput($type, $input, null);

        if ($errors === [] && ! $this->policy->canEdit($user, $type, $input)) {
            $errors['department_id'] = 'You cannot publish in that department or category.';
        }

        if ($errors !== []) {
            return $this->form($type, null, $input, $errors);
        }

        try {
            $id = $this->content->save($type, null, $input, (int) $user->id);
        } catch (InvalidArgumentException $e) {
            return $this->form($type, null, $input, ['_form' => $e->getMessage()]);
        }

        return redirect()->to(site_url("admin/content/{$type}/{$id}"))->with('message', 'Saved.');
    }

    public function edit(string $type, int $id): string
    {
        return $this->form($type, $this->record($type, $id), null);
    }

    public function update(string $type, int $id): ResponseInterface|RedirectResponse|string
    {
        $record = $this->record($type, $id);
        $user   = $this->user();

        if (! $this->policy->canEdit($user, $type, $record)) {
            return $this->forbidden();
        }

        $input  = $this->input($type, $record);
        $errors = $this->validateInput($type, $input, $record);

        if ($errors === [] && ! $this->policy->canEdit($user, $type, $input + $record)) {
            $errors['department_id'] = 'You cannot move this item to that department or category.';
        }

        if ($errors !== []) {
            return $this->form($type, $record, $input, $errors);
        }

        try {
            $this->content->save($type, $id, $input, (int) $user->id);
        } catch (InvalidArgumentException $e) {
            return $this->form($type, $record, $input, ['_form' => $e->getMessage()]);
        }

        return redirect()->to(site_url("admin/content/{$type}/{$id}"))->with('message', 'Saved.');
    }

    public function publish(string $type, int $id): ResponseInterface|RedirectResponse
    {
        return $this->changeStatus($type, $id, true);
    }

    public function unpublish(string $type, int $id): ResponseInterface|RedirectResponse
    {
        return $this->changeStatus($type, $id, false);
    }

    public function delete(string $type, int $id): ResponseInterface|RedirectResponse
    {
        $record = $this->record($type, $id);

        if (! $this->policy->canEdit($this->user(), $type, $record)) {
            return $this->forbidden();
        }

        try {
            $this->content->delete($type, $id, (int) $this->user()->id);
        } catch (InvalidArgumentException $e) {
            return redirect()->to(site_url("admin/content/{$type}/{$id}"))->with('error', $e->getMessage());
        }

        return redirect()->to(site_url("admin/content/{$type}"))->with('message', 'Deleted.');
    }

    public function revisions(string $type, int $id): string
    {
        $record = $this->record($type, $id);

        if (empty($this->definition($type)['revisions'])) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('admin/content/revisions', [
            'type'       => $type,
            'definition' => $this->definition($type),
            'record'     => $record,
            'revisions'  => $this->content->revisions($type, $id),
            'canRestore' => $this->policy->canRestore($this->user(), $type, $record),
        ]);
    }

    public function restore(string $type, int $id, int $revisionId): ResponseInterface|RedirectResponse
    {
        $record   = $this->record($type, $id);
        $revision = $this->content->revision($type, $id, $revisionId);
        $user     = $this->user();

        if ($revision === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        // The restored values must also be within the user's scope.
        if (! $this->policy->canRestore($user, $type, $record) || ! $this->policy->canEdit($user, $type, $revision['data'] + $record)) {
            return $this->forbidden();
        }

        $this->content->restore($type, $id, $revisionId, (int) $user->id);

        return redirect()->to(site_url("admin/content/{$type}/{$id}"))->with('message', 'Revision ' . $revision['revision_number'] . ' restored.');
    }

    public function addMedia(string $type, int $id): ResponseInterface|RedirectResponse
    {
        $record = $this->record($type, $id);
        $user   = $this->user();

        if (empty($this->definition($type)['media']) || ! $this->policy->canEdit($user, $type, $record)) {
            return $this->forbidden();
        }

        $back  = redirect()->to(site_url("admin/content/{$type}/{$id}") . '#media');
        $kind  = (string) $this->request->getPost('kind');
        $title = trim((string) $this->request->getPost('title'));
        $alt   = trim((string) $this->request->getPost('alt_text'));
        $url   = trim((string) $this->request->getPost('external_url'));

        if ($title === '' || mb_strlen($title) > 255) {
            return $back->with('error', 'Give the file a title of up to 255 characters.');
        }

        if ($kind === 'image' && $alt === '') {
            return $back->with('error', 'Describe the image for people who cannot see it.');
        }

        try {
            if ($url !== '') {
                service('media')->storeExternal($kind, $url, $title, $type, $id, (int) $user->id);
            } else {
                $file = $this->request->getFile('file');
                if (($error = service('media')->uploadError($file, $kind)) !== null) {
                    return $back->with('error', $error);
                }
                service('media')->storeUpload($file, $kind, $title, $alt === '' ? null : mb_substr($alt, 0, 500), $type, $id, (int) $user->id);
            }
        } catch (InvalidArgumentException $e) {
            return $back->with('error', $e->getMessage());
        }

        return $back->with('message', 'File added.');
    }

    public function removeMedia(string $type, int $id, int $linkId): ResponseInterface|RedirectResponse
    {
        $record = $this->record($type, $id);

        if (! $this->policy->canEdit($this->user(), $type, $record)) {
            return $this->forbidden();
        }

        service('media')->detach($linkId, $type, $id, (int) $this->user()->id);

        return redirect()->to(site_url("admin/content/{$type}/{$id}") . '#media')->with('message', 'File removed.');
    }

    /**
     * Records that a mission serves a country, resident or non-resident.
     */
    public function addCoverage(int $id): ResponseInterface|RedirectResponse
    {
        $this->record('missions', $id);

        if (! $this->user()->can('missions.manage')) {
            return $this->forbidden();
        }

        $back      = redirect()->to(site_url("admin/content/missions/{$id}") . '#coverage');
        $countryId = (int) $this->request->getPost('country_id');
        $coverage  = (string) $this->request->getPost('coverage');
        $db        = db_connect();

        if (! in_array($coverage, ['resident', 'non_resident'], true) || $db->table('countries')->where('id', $countryId)->countAllResults() === 0) {
            return $back->with('error', 'Choose a country and whether the mission is resident there.');
        }

        $db->table('mission_coverage')->where(['mission_id' => $id, 'country_id' => $countryId])->delete();
        $db->table('mission_coverage')->insert(['mission_id' => $id, 'country_id' => $countryId, 'coverage' => $coverage]);
        service('auditLog')->record('missions.coverage_added', (int) $this->user()->id, 'missions', $id, ['country_id' => $countryId, 'coverage' => $coverage]);

        return $back->with('message', 'Country coverage saved.');
    }

    public function removeCoverage(int $id, int $countryId): ResponseInterface|RedirectResponse
    {
        $this->record('missions', $id);

        if (! $this->user()->can('missions.manage')) {
            return $this->forbidden();
        }

        db_connect()->table('mission_coverage')->where(['mission_id' => $id, 'country_id' => $countryId])->delete();
        service('auditLog')->record('missions.coverage_removed', (int) $this->user()->id, 'missions', $id, ['country_id' => $countryId]);

        return redirect()->to(site_url("admin/content/missions/{$id}") . '#coverage')->with('message', 'Country coverage removed.');
    }

    private function changeStatus(string $type, int $id, bool $publish): ResponseInterface|RedirectResponse
    {
        $record = $this->record($type, $id);

        if (empty($this->definition($type)['status']) || ! $this->policy->canEdit($this->user(), $type, $record)) {
            return $this->forbidden();
        }

        $publish
            ? $this->content->publish($type, $id, (int) $this->user()->id)
            : $this->content->unpublish($type, $id, (int) $this->user()->id);

        return redirect()->to(site_url("admin/content/{$type}/{$id}"))->with('message', $publish ? 'Published.' : 'Unpublished.');
    }

    /**
     * @param array<string, mixed>|null $record
     * @param array<string, mixed>|null $values
     * @param array<string, string>     $errors
     */
    private function form(string $type, ?array $record, ?array $values, array $errors = []): string
    {
        $definition = $this->definition($type);
        $user       = $this->user();
        $values ??= $record;

        $options = [];
        foreach ($definition['fields'] as $name => $field) {
            if ($field['type'] === 'select' || $field['type'] === 'department') {
                $options[$name] = service('contentOptions')->for($field['options'] ?? 'department', $user, $this->locale(), $record);
            }
        }

        $data = [
            'type'       => $type,
            'definition' => $definition,
            'record'     => $record,
            'values'     => $values,
            'errors'     => $errors,
            'options'    => $options,
            'canEdit'    => $record === null ? $this->policy->canCreate($user, $type) : $this->policy->canEdit($user, $type, $record),
            'media'      => $record !== null && ! empty($definition['media']) ? service('media')->forOwner($type, (int) $record['id']) : [],
            'mediaKinds' => config(Media::class)->kinds,
            'coverage'   => [],
            'countries'  => [],
            'publicUrl'  => $record === null ? null : $this->publicUrl($type, $record),
        ];

        if ($type === 'missions' && $record !== null) {
            $data['coverage']  = service('missionDirectory')->coverageOf((int) $record['id']);
            $data['countries'] = service('contentOptions')->for('countries', $user, $this->locale());
        }

        return view('admin/content/form', $data);
    }

    /**
     * @param array<string, mixed>|null $record
     *
     * @return array<string, mixed>
     */
    private function input(string $type, ?array $record = null): array
    {
        $input = [];

        foreach ($this->definition($type)['fields'] as $name => $field) {
            if (! empty($record['is_system']) && ! empty($field['lockedForSystem'])) {
                continue;
            }

            $value        = $this->request->getPost($name);
            $input[$name] = is_string($value) ? $value : ($field['type'] === 'checkbox' ? ($value !== null ? '1' : '') : '');
        }

        // A mission is linked only to mission activities.
        if ($type === 'articles' && ($input['category'] ?? '') !== 'mission_activity') {
            $input['mission_id'] = '';
        }

        return $input;
    }

    /**
     * @param array<string, mixed>      $input
     * @param array<string, mixed>|null $record
     *
     * @return array<string, string>
     */
    private function validateInput(string $type, array $input, ?array $record): array
    {
        $definition = $this->definition($type);
        $rules      = [];

        foreach ($definition['fields'] as $name => $field) {
            if (array_key_exists($name, $input) && isset($field['rules'])) {
                $rules[$name] = ['label' => $field['label'], 'rules' => $field['rules']];
            }
        }

        $validation = service('validation');
        $validation->setRules($rules);
        $validation->run($input);
        $errors = $validation->getErrors();

        foreach ($definition['fields'] as $name => $field) {
            $value = $input[$name] ?? '';

            if (isset($errors[$name]) || $value === '' || ! in_array($field['type'], ['select', 'department'], true)) {
                continue;
            }

            $choices = service('contentOptions')->for($field['options'] ?? 'department', $this->user(), $this->locale(), $record);

            if (! array_key_exists((string) $value, $choices)) {
                $errors[$name] = 'Choose one of the listed options for ' . $field['label'] . '.';
            }
        }

        if ($type === 'articles' && ($input['category'] ?? '') === 'mission_activity' && ($input['mission_id'] ?? '') === '') {
            $errors['mission_id'] = 'Choose the mission this activity belongs to.';
        }

        if ($this->policy->isDiasporaContent($type, $input) && ! isset($errors['department_id'])) {
            $departmentId = (int) ($input['department_id'] ?? 0);
            if ($departmentId === 0 || ! service('departmentAccess')->isDiasporaDepartment($departmentId)) {
                $errors['department_id'] = 'Diaspora content must belong to a diaspora department.';
            }
        }

        if ($type === 'diaspora_countries' && ($input['country_id'] ?? '') !== '' && ! isset($errors['country_id'])) {
            $taken = db_connect()->table('diaspora_country_pages')->where(['country_id' => (int) $input['country_id'], 'locale' => $record['locale'] ?? $this->locale()]);
            if ($record !== null) {
                $taken->where('id !=', $record['id']);
            }
            if ($taken->countAllResults() > 0) {
                $errors['country_id'] = 'This country already has a Diaspora by Country page.';
            }
        }

        if ($type === 'notification_rules' && isset($input['recipients']) && ! isset($errors['recipients'])) {
            $lines = array_filter(array_map('trim', preg_split('/[\s,;]+/', $input['recipients']) ?: []));
            if (count(\App\Libraries\SubmissionService::recipients($input['recipients'])) !== count(array_unique($lines))) {
                $errors['recipients'] = 'Enter valid email addresses, one per line.';
            }
        }

        if ($type === 'pages' && isset($input['external_links']) && trim($input['external_links']) !== '') {
            $lines = array_filter(array_map('trim', preg_split('/\R/', $input['external_links'])));
            if (count(parse_external_links($input['external_links'])) !== count($lines)) {
                $errors['external_links'] = 'Write each link as: Label | https://address';
            }
        }

        if ($type === 'countries' && isset($input['iso_code']) && ! isset($errors['iso_code'])) {
            $taken = db_connect()->table('countries')->where('iso_code', strtoupper($input['iso_code']));
            if ($record !== null) {
                $taken->where('id !=', $record['id']);
            }
            if ($taken->countAllResults() > 0) {
                $errors['iso_code'] = 'Another country already uses this code.';
            }
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(string $type): array
    {
        $values = [];
        foreach ($this->definition($type)['fields'] as $name => $field) {
            $values[$name] = $field['type'] === 'checkbox' && in_array($name, ['is_active'], true) ? 1 : null;
        }

        $section = $this->request->getGet('section');
        if ($type === 'pages' && is_string($section) && isset($this->types->pageSections[$section])) {
            $values['section'] = $section;
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $type): array
    {
        $definition = $this->types->type($type);

        if ($definition === []) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $definition;
    }

    /**
     * @return array<string, mixed>
     */
    private function record(string $type, int $id): array
    {
        $this->definition($type);
        $record = $this->content->find($type, $id);

        if ($record === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $record;
    }

    /**
     * @return array<string, string>
     */
    private function listLabels(string $type): array
    {
        $labels = ['status' => 'Status', 'published_at' => 'Published', 'path' => 'Address'];
        foreach ($this->definition($type)['fields'] as $name => $field) {
            $labels[$name] = $field['label'];
        }

        return $labels;
    }

    /**
     * @param array<string, mixed> $record
     */
    private function publicUrl(string $type, array $record): ?string
    {
        helper('site');

        if (($record['status'] ?? 'published') !== 'published') {
            return null;
        }

        $locale = $record['locale'] ?? $this->locale();

        return match ($type) {
            'articles'  => in_array($record['category'], $this->types->diasporaArticleCategories, true)
                ? site_url($locale . '/diaspora/stay-informed/' . $record['slug'])
                : site_url($locale . '/news-press/' . match ($record['category']) {
                    'statement' => 'statements', 'mission_activity' => 'mission-activities', default => 'news',
                } . '/' . $record['slug']),
            'diaspora_countries' => site_url($locale . '/diaspora/countries/' . $record['slug']),
            'pages'     => site_url($locale . '/' . $record['section'] . '/' . $record['path']),
            'galleries' => site_url($locale . '/news-press/gallery/' . $record['slug']),
            'archives'  => site_url($locale . '/archives/' . $record['slug']),
            'missions'  => site_url($locale . '/missions/profile/' . $record['slug']),
            default     => null,
        };
    }

    private function locale(): string
    {
        return config('App')->defaultLocale;
    }

    private function user(): User
    {
        return auth()->user();
    }

    private function forbidden(): ResponseInterface
    {
        return $this->response->setStatusCode(403)->setBody(view('admin/forbidden'));
    }
}

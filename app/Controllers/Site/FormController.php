<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use CodeIgniter\HTTP\RedirectResponse;

/**
 * Shared handling for public forms: closed until the Ministry's approved
 * text exists, spam checks, validation and plain-text clean-up.
 *
 * Invalid forms are shown again directly (not through a redirect), so the
 * visitor's entries are never stored in the session.
 */
abstract class FormController extends SiteController
{
    /**
     * A form is open only when every text block it needs holds approved text.
     */
    protected function formOpen(string $form): bool
    {
        foreach (config('Submissions')->requiredBlocks[$form] ?? [] as $key) {
            if ($this->blockHtml($key) === '') {
                return false;
            }
        }

        return true;
    }

    protected function blockHtml(string $key): string
    {
        $row = db_connect()->table('site_blocks')
            ->where(['block_key' => $key, 'locale' => $this->locale()])
            ->get()
            ->getRowArray();

        return service('markdown')->toHtml($row['body'] ?? null);
    }

    /**
     * Spam and age checks; null when the submission may be processed.
     */
    protected function guardError(string $form): ?string
    {
        return service('formGuard')->check($this->request, $form);
    }

    /**
     * Posted text fields, trimmed and stripped of control characters.
     *
     * @param list<string> $names
     *
     * @return array<string, string>
     */
    protected function posted(array $names): array
    {
        $values = [];
        foreach ($names as $name) {
            $value         = $this->request->getPost($name);
            $values[$name] = is_string($value) ? trim(preg_replace('/[^\P{C}\n\t]/u', '', $value) ?? '') : '';
        }

        return $values;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, mixed>  $rules
     *
     * @return array<string, string>
     */
    protected function validateValues(array $values, array $rules): array
    {
        $validation = service('validation');
        $validation->reset();
        $validation->setRules($rules);
        $validation->run($values);

        return $validation->getErrors();
    }

    /**
     * Empty strings become null for optional database columns.
     *
     * @param array<string, string> $values
     *
     * @return array<string, string|null>
     */
    protected function nullable(array $values): array
    {
        return array_map(static fn (string $v): ?string => $v === '' ? null : $v, $values);
    }

    protected function toLocale(string $path): RedirectResponse
    {
        return redirect()->to(site_url($this->locale() . '/' . $path));
    }

    protected static function phoneRule(): string
    {
        return 'permit_empty|max_length[50]|regex_match[/^[0-9+()\-.\s]{5,50}$/]';
    }
}

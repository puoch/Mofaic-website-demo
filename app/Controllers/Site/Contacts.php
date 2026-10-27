<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use CodeIgniter\HTTP\RedirectResponse;

/**
 * CONTACTS: official contact information entered by Ministry
 * Administrators (CON-01) and the public inquiry form, which feeds the Public
 * Inquiries Inbox (CON-02 to CON-04). The inquiry form is not a channel for
 * visa or consular applications (brief section 5).
 */
class Contacts extends FormController
{
    public function index(): string
    {
        return $this->page([], []);
    }

    public function submit(): RedirectResponse|string
    {
        if (! $this->formOpen('inquiry')) {
            return $this->page([], []);
        }

        $values = $this->posted(['name', 'email', 'phone', 'subject', 'message']);

        if (($error = $this->guardError('inquiry')) !== null) {
            return $this->page($values, ['form' => $error]);
        }

        $errors = $this->validateValues($values, $this->rules());

        if ($errors !== []) {
            return $this->page($values, $errors);
        }

        $saved = service('submissions')->create('inquiry', $this->nullable($values), $this->locale());

        return $this->toLocale('contacts/thanks')->with('reference', $saved['reference']);
    }

    public function thanks(): RedirectResponse|string
    {
        $reference = session('reference');

        if (! is_string($reference)) {
            return $this->toLocale('contacts');
        }

        return view('site/thanks', [
            'heading'   => lang('Site.nav.contacts'),
            'crumbs'    => [[lang('Site.nav.contacts'), locale_url('contacts')], [lang('Forms.inquiryHeading')]],
            'message'   => lang('Forms.inquiryThanks'),
            'reference' => $reference,
        ]);
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     */
    private function page(array $values, array $errors): string
    {
        $open = $this->formOpen('inquiry');

        return view('site/contacts', [
            'heading'     => lang('Site.nav.contacts'),
            'contactHtml' => $this->blockHtml('contact_information'),
            'formOpen'    => $open,
            'privacyHtml' => $open ? $this->blockHtml('privacy_notice') : '',
            'required'    => config('Submissions')->inquiryFields,
            'values'      => $values,
            'errors'      => $errors,
        ]);
    }

    /**
     * @return array<string, array{label: string, rules: string}>
     */
    private function rules(): array
    {
        $required = config('Submissions')->inquiryFields;
        $need     = static fn (string $field): string => $required[$field] ? 'required' : 'permit_empty';

        return [
            'name'    => ['label' => lang('Forms.name'), 'rules' => $need('name') . '|max_length[190]'],
            'email'   => ['label' => lang('Forms.email'), 'rules' => $need('email') . '|valid_email|max_length[190]'],
            'phone'   => ['label' => lang('Forms.phone'), 'rules' => $required['phone'] ? 'required|' . substr(self::phoneRule(), 12) : self::phoneRule()],
            'subject' => ['label' => lang('Forms.subject'), 'rules' => $need('subject') . '|max_length[190]'],
            'message' => ['label' => lang('Forms.message'), 'rules' => $need('message') . '|max_length[5000]'],
        ];
    }
}

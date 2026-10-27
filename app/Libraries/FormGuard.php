<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\HTTP\IncomingRequest;
use Config\Submissions;
use RuntimeException;

/**
 * Spam protection for public forms without outside services (PD-11):
 * a hidden field people leave empty, a signed time stamp that rejects forms
 * sent too quickly or too late, and a per-address hourly limit. CSRF tokens
 * are checked separately by the csrf filter.
 *
 * Visitor addresses are used only as hashed rate-limit keys; they are not
 * stored with submissions.
 */
class FormGuard
{
    public function __construct(private readonly Submissions $config)
    {
    }

    /**
     * Hidden inputs to print inside a public form.
     *
     * @return array<string, string> name => value
     */
    public function fields(string $form, ?int $issuedAt = null): array
    {
        return [
            $this->config->honeypotField => '',
            'form_token'                 => self::token($form, $issuedAt ?? time()),
        ];
    }

    /**
     * Returns an error message for the visitor, or null when the form may be
     * processed. Each accepted check counts towards the hourly limit.
     */
    public function check(IncomingRequest $request, string $form): ?string
    {
        if ($request->getFiles() !== []) {
            return lang('Forms.noFiles');
        }

        if ((string) $request->getPost($this->config->honeypotField) !== '') {
            return lang('Forms.notSent');
        }

        $issuedAt = self::verify((string) $request->getPost('form_token'), $form);
        $age      = $issuedAt === null ? null : time() - $issuedAt;

        if ($age === null || $age > $this->config->maximumFormAgeSeconds) {
            return lang('Forms.expired');
        }

        if ($age < $this->config->minimumFillSeconds) {
            return lang('Forms.tooFast');
        }

        $key = 'form-' . $form . '-' . hash('sha256', $request->getIPAddress());

        if (! service('throttler')->check($key, $this->config->submissionsPerHour, HOUR)) {
            return lang('Forms.tooMany');
        }

        return null;
    }

    public static function token(string $form, int $issuedAt): string
    {
        return $issuedAt . '.' . hash_hmac('sha256', $form . '|' . $issuedAt, self::secret());
    }

    /**
     * Returns the time the token was issued, or null if it is not valid.
     */
    public static function verify(string $token, string $form): ?int
    {
        if (preg_match('/^(\d{1,12})\.([0-9a-f]{64})$/', $token, $m) !== 1) {
            return null;
        }

        return hash_equals(hash_hmac('sha256', $form . '|' . $m[1], self::secret()), $m[2]) ? (int) $m[1] : null;
    }

    /**
     * Key for signing form tokens and newsletter links. Production must set
     * encryption.key in .env.
     */
    public static function secret(): string
    {
        $key = (string) config('Encryption')->key;

        if ($key !== '') {
            return $key;
        }

        if (ENVIRONMENT === 'production') {
            throw new RuntimeException('Set encryption.key in .env before opening public forms.');
        }

        return 'development-only-signing-key';
    }
}

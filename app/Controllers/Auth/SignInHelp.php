<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Authentication\Passwords\ValidationRules as PasswordRules;

/**
 * Staff sign-in help: "Forgot password", choosing a password from an
 * emailed link, and "Request an account". See PasswordLinks and
 * AccountRequests for the rules.
 */
class SignInHelp extends BaseController
{
    /** "Forgot password" requests allowed per visitor address per hour. */
    private const RESETS_PER_HOUR = 5;

    public function forgot(): string|RedirectResponse
    {
        if (auth()->loggedIn()) {
            return redirect()->to(site_url('admin'));
        }

        return view('auth/forgot_password', ['sent' => session('sent') === true]);
    }

    public function sendLink(): RedirectResponse
    {
        $ip = hash('sha256', $this->request->getIPAddress());

        if (! service('throttler')->check('password-reset-' . $ip, self::RESETS_PER_HOUR, HOUR)) {
            return redirect()->to(site_url('login/forgot-password'))->with('error', lang('StaffAccess.tooMany'));
        }

        $email = trim((string) $this->request->getPost('email'));

        if ($email !== '' && strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && service('throttler')->check('password-reset-' . hash('sha256', mb_strtolower($email)), 3, HOUR)) {
            service('passwordLinks')->requestReset($email);
        }

        // The same reply whether or not the address has an account.
        return redirect()->to(site_url('login/forgot-password'))->with('sent', true);
    }

    public function resetForm(string $token = ''): string|ResponseInterface
    {
        $this->response->setHeader('Referrer-Policy', 'no-referrer');
        $user = service('passwordLinks')->find($token);

        return $this->response->setBody(view('auth/reset_password', [
            'token'  => $user === null ? '' : $token,
            'errors' => [],
        ]));
    }

    public function resetSave(): string|ResponseInterface|RedirectResponse
    {
        $this->response->setHeader('Referrer-Policy', 'no-referrer');
        $token = (string) $this->request->getPost('token');
        $user  = service('passwordLinks')->find($token);

        if ($user === null) {
            return $this->response->setBody(view('auth/reset_password', ['token' => '', 'errors' => []]));
        }

        $password = $this->request->getPost('password');
        $confirm  = $this->request->getPost('password_confirm');
        $password = is_string($password) ? $password : '';

        if (! in_array(PasswordRules::class, config('Validation')->ruleSets, true)) {
            config('Validation')->ruleSets[] = PasswordRules::class;
        }
        $validation = service('validation', null, false);
        $validation->setRules([
            'password' => ['label' => lang('StaffAccess.newPassword'), 'rules' => ['required', 'max_byte[72]', 'strong_password[]']],
        ]);
        $ok     = $validation->run(['password' => $password, 'email' => (string) $user->email, 'username' => (string) $user->username]);
        $errors = $ok ? [] : $validation->getErrors();

        if ($errors === [] && $password !== $confirm) {
            $errors['password_confirm'] = lang('StaffAccess.passwordsDiffer');
        }

        if ($errors !== []) {
            return $this->response->setBody(view('auth/reset_password', ['token' => $token, 'errors' => $errors]));
        }

        service('passwordLinks')->setPassword($user, $password);

        return redirect()->to(site_url('login'))->with('message', lang('StaffAccess.passwordSaved'));
    }

    public function requestForm(): string
    {
        return view('auth/request_account', $this->requestData());
    }

    public function requestSend(): string|RedirectResponse
    {
        if (! $this->requestsOpen()) {
            return view('auth/request_account', $this->requestData());
        }

        $guard = service('formGuard')->check($this->request, 'account-request');
        if ($guard !== null) {
            return view('auth/request_account', $this->requestData(['form' => $guard]));
        }

        $values = [];
        foreach (['full_name', 'email', 'office', 'job_title', 'reason'] as $name) {
            $value         = $this->request->getPost($name);
            $values[$name] = is_string($value) ? trim(preg_replace('/[^\P{C}\n\t]/u', '', $value) ?? '') : '';
        }

        $validation = service('validation', null, false);
        $validation->setRules([
            'full_name' => ['label' => lang('StaffAccess.fullName'), 'rules' => 'required|max_length[120]'],
            'email'     => ['label' => lang('StaffAccess.workEmail'), 'rules' => 'required|max_length[254]|valid_email'],
            'office'    => ['label' => lang('StaffAccess.office'), 'rules' => 'required|max_length[160]'],
            'job_title' => ['label' => lang('StaffAccess.jobTitle'), 'rules' => 'required|max_length[120]'],
            'reason'    => ['label' => lang('StaffAccess.reason'), 'rules' => 'required|max_length[1000]'],
        ]);

        if (! $validation->run($values)) {
            return view('auth/request_account', $this->requestData(['errors' => $validation->getErrors(), 'values' => $values]));
        }

        service('accountRequests')->submit($values);

        return redirect()->to(site_url('login/request-account'))->with('sent', true);
    }

    /**
     * The form collects personal details, so like the public forms it stays
     * closed until the Ministry's privacy notice is published.
     */
    private function requestsOpen(): bool
    {
        return $this->privacyNotice() !== '';
    }

    private function privacyNotice(): string
    {
        $row = db_connect()->table('site_blocks')
            ->where(['block_key' => 'privacy_notice', 'locale' => config('App')->defaultLocale])
            ->get()->getRowArray();

        return service('markdown')->toHtml($row['body'] ?? null);
    }

    /**
     * @param array<string, mixed> $extra
     *
     * @return array<string, mixed>
     */
    private function requestData(array $extra = []): array
    {
        return $extra + [
            'sent'    => session('sent') === true,
            'privacy' => $this->privacyNotice(),
            'errors'  => [],
            'values'  => [],
            'form'    => null,
        ];
    }
}

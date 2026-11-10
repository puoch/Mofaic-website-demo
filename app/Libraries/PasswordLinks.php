<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Single-use links that let a staff member choose a new password: "Forgot
 * password" (valid for one hour) and the first password of an approved
 * account request (valid for 48 hours).
 *
 * Tokens are 32 random bytes. Only their SHA-256 hash is stored, a new link
 * cancels the older ones, and using a link deletes it. Setting a password
 * signs the account out of every "Remember me" browser. Sign-in still needs
 * the emailed code afterwards.
 */
class PasswordLinks
{
    public const RESET   = 'reset';
    public const WELCOME = 'welcome';

    /**
     * @var array<string, int> seconds each kind of link stays valid
     */
    public const LIFETIME = [
        self::RESET   => HOUR,
        self::WELCOME => 2 * DAY,
    ];

    public function __construct(private readonly BaseConnection $db)
    {
    }

    /**
     * Creates a link for $user and returns the full URL.
     */
    public function issue(User $user, string $purpose): string
    {
        $token = bin2hex(random_bytes(32));
        $now   = Time::now('UTC');

        $this->db->table('staff_password_tokens')->where('user_id', $user->id)->delete();
        $this->db->table('staff_password_tokens')->insert([
            'user_id'    => (int) $user->id,
            'purpose'    => $purpose,
            'token_hash' => hash('sha256', $token),
            'expires_at' => $now->addSeconds(self::LIFETIME[$purpose])->toDateTimeString(),
            'created_at' => $now->toDateTimeString(),
        ]);

        return site_url('login/reset-password/' . $token);
    }

    /**
     * "Forgot password": emails a link when $email belongs to an active,
     * unbanned account. Says nothing either way, so the form does not show
     * who has an account.
     */
    public function requestReset(string $email): void
    {
        $user = (new UserModel())->findByCredentials(['email' => $email]);

        if ($user === null || ! $user->isActivated() || $user->isBanned()) {
            return;
        }

        $link = $this->issue($user, self::RESET);

        service('mailQueue')->queue('password-reset', (string) $user->email, lang('StaffAccess.resetEmailSubject'), lang('StaffAccess.resetEmailBody', [$link]));
        service('auditLog')->record('staff.password-reset-requested', null, 'user', (int) $user->id);
    }

    /**
     * The account a link belongs to, or null if the link is unknown,
     * expired or the account cannot sign in.
     */
    public function find(string $token): ?User
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return null;
        }

        $row = $this->db->table('staff_password_tokens')
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at >', Time::now('UTC')->toDateTimeString())
            ->get()->getRowArray();

        if ($row === null) {
            return null;
        }

        $user = model(UserModel::class)->findById((int) $row['user_id']);

        return $user === null || $user->isBanned() ? null : $user;
    }

    /**
     * Sets the new password, deletes the link and every "Remember me"
     * login, and emails the account holder that the password changed.
     */
    public function setPassword(User $user, string $password): void
    {
        $user->setPassword($password);
        model(UserModel::class)->save($user);

        $this->db->table('staff_password_tokens')->where('user_id', $user->id)->delete();
        $this->db->table('auth_remember_tokens')->where('user_id', $user->id)->delete();

        service('mailQueue')->queue('password-changed', (string) $user->email, lang('StaffAccess.changedEmailSubject'), lang('StaffAccess.changedEmailBody'));
        service('auditLog')->record('staff.password-set', null, 'user', (int) $user->id, ['via' => 'emailed link']);
    }
}

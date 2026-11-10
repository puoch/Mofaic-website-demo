<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use InvalidArgumentException;

/**
 * "Request an account" on the staff sign-in page. A request gives no access:
 * a Super Administrator (users.manage) approves it, choosing the role, or
 * declines it on the staff screen. On approval the account is created with
 * an unknown random password and the person is emailed a link, valid for
 * 48 hours, to choose their own. Every decision is audited.
 *
 * The person sees the same reply whether or not the address already has an
 * account or a pending request, so the form does not show who works here.
 */
class AccountRequests
{
    public function __construct(private readonly BaseConnection $db)
    {
    }

    /**
     * @param array{full_name: string, email: string, office: string, job_title: string, reason: string} $values already validated
     */
    public function submit(array $values): void
    {
        $email = mb_strtolower($values['email']);

        if ($this->db->table('staff_account_requests')->where(['email' => $email, 'status' => 'pending'])->countAllResults() > 0) {
            return;
        }

        $this->db->table('staff_account_requests')->insert([
            'full_name'  => $values['full_name'],
            'email'      => $email,
            'office'     => $values['office'],
            'job_title'  => $values['job_title'],
            'reason'     => $values['reason'],
            'status'     => 'pending',
            'created_at' => Time::now('UTC')->toDateTimeString(),
        ]);
        $id = (int) $this->db->insertID();

        service('auditLog')->record('staff.account-requested', null, 'account_request', $id);

        foreach ($this->superAdminEmails() as $to) {
            service('mailQueue')->queue('account-request', $to, lang('StaffAccess.newRequestEmailSubject'), lang('StaffAccess.newRequestEmailBody', [site_url('admin/staff#account-requests')]));
        }
    }

    /**
     * @return list<array<string, mixed>> pending requests, oldest first, each with has_account
     */
    public function pending(): array
    {
        $rows = $this->db->table('staff_account_requests')->where('status', 'pending')->orderBy('created_at')->orderBy('id')->get()->getResultArray();

        foreach ($rows as &$row) {
            $row['has_account'] = (new UserModel())->findByCredentials(['email' => $row['email']]) !== null;
        }

        return $rows;
    }

    /**
     * Creates the account with $group and emails the set-password link.
     *
     * @return User|array<string, string> the new account, or validation errors
     */
    public function approve(int $id, string $username, string $group, User $actor): User|array
    {
        $request = $this->find($id);

        // Nobody knows this password; the person chooses their own from the emailed link.
        $password = bin2hex(random_bytes(16)) . 'Aa!9';
        $result   = service('staffAccounts')->create(
            ['username' => $username, 'email' => $request['email'], 'password' => $password, 'group' => $group],
            $actor,
            ['via' => 'account request', 'request' => $id],
        );

        if (is_array($result)) {
            return $result;
        }

        $this->decide($id, 'approved', $actor, (int) $result->id);

        $link = service('passwordLinks')->issue($result, PasswordLinks::WELCOME);
        service('mailQueue')->queue('account-approved', $request['email'], lang('StaffAccess.approvedEmailSubject'), lang('StaffAccess.approvedEmailBody', [$link, site_url('login')]));

        return $result;
    }

    public function decline(int $id, User $actor): void
    {
        $request = $this->find($id);
        $this->decide($id, 'declined', $actor, null);

        service('mailQueue')->queue('account-declined', $request['email'], lang('StaffAccess.declinedEmailSubject'), lang('StaffAccess.declinedEmailBody'));
    }

    /**
     * @return array<string, mixed>
     */
    private function find(int $id): array
    {
        $row = $this->db->table('staff_account_requests')->where(['id' => $id, 'status' => 'pending'])->get()->getRowArray();

        if ($row === null) {
            throw new InvalidArgumentException('That request has already been dealt with.');
        }

        return $row;
    }

    private function decide(int $id, string $status, User $actor, ?int $userId): void
    {
        $this->db->table('staff_account_requests')->where('id', $id)->update([
            'status'     => $status,
            'user_id'    => $userId,
            'decided_by' => (int) $actor->id,
            'decided_at' => Time::now('UTC')->toDateTimeString(),
        ]);

        service('auditLog')->record('staff.account-request-' . $status, (int) $actor->id, 'account_request', $id, $userId === null ? [] : ['user' => $userId]);
    }

    /**
     * @return list<string>
     */
    private function superAdminEmails(): array
    {
        $emails = [];
        foreach (model(UserModel::class)->where('active', 1)->findAll() as $account) {
            if (! $account->isBanned() && $account->inGroup('superadmin') && (string) $account->email !== '') {
                $emails[] = (string) $account->email;
            }
        }

        return $emails;
    }
}

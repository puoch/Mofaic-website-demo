<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use InvalidArgumentException;

/**
 * Diaspora newsletter (brief section 2, Stay Informed).
 *
 * Enrolment is a separate, voluntary opt-in. Subscriptions are confirmed by
 * a link sent to the address (double opt-in, PD-09), and every issue carries
 * an unsubscribe link. Subscription records are kept apart from diaspora
 * registrations.
 */
class Newsletter
{
    /** A confirmation link stops working after this many seconds. */
    public const CONFIRM_LIFETIME = 7 * DAY;

    /** A pending address is not sent another confirmation within this time. */
    public const RESEND_AFTER = 10 * MINUTE;

    public function __construct(
        private readonly BaseConnection $db,
        private readonly MailQueue $mail,
        private readonly AuditLog $audit,
    ) {
    }

    /**
     * Starts a subscription and queues the confirmation email. Says nothing
     * about whether the address was already subscribed.
     */
    public function subscribe(string $email, string $source, string $locale): void
    {
        $email    = mb_strtolower(trim($email));
        $now      = Time::now('UTC');
        $existing = $this->db->table('newsletter_subscriptions')->where('email', $email)->get()->getRowArray();

        if ($existing !== null && $existing['status'] === 'confirmed') {
            return;
        }

        if ($existing !== null && $existing['status'] === 'pending' && $existing['confirm_sent_at'] !== null
            && Time::parse($existing['confirm_sent_at'], 'UTC')->getTimestamp() > $now->getTimestamp() - self::RESEND_AFTER) {
            return;
        }

        $token  = bin2hex(random_bytes(32));
        $values = [
            'status'             => 'pending',
            'locale'             => $locale,
            'confirm_token_hash' => hash('sha256', $token),
            'confirm_sent_at'    => $now->toDateTimeString(),
            'updated_at'         => $now->toDateTimeString(),
        ];

        if ($existing === null) {
            $this->db->table('newsletter_subscriptions')->insert($values + ['email' => $email, 'source' => $source, 'created_at' => $now->toDateTimeString()]);
            $id = (int) $this->db->insertID();
        } else {
            $id = (int) $existing['id'];
            $this->db->table('newsletter_subscriptions')->where('id', $id)->update($values);
        }

        $this->mail->queue(
            'newsletter-confirm',
            $email,
            lang('Forms.newsletterConfirmSubject'),
            lang('Forms.newsletterConfirmBody', [site_url($locale . '/diaspora/newsletter/confirm/' . $token)]),
        );
        $this->audit->record('newsletter.requested', null, 'newsletter_subscription', $id, ['source' => $source]);
    }

    public function confirm(string $token): bool
    {
        if (preg_match('/^[0-9a-f]{64}$/', $token) !== 1) {
            return false;
        }

        $row = $this->db->table('newsletter_subscriptions')
            ->where(['confirm_token_hash' => hash('sha256', $token), 'status' => 'pending'])
            ->get()
            ->getRowArray();

        if ($row === null || Time::parse($row['confirm_sent_at'], 'UTC')->getTimestamp() < time() - self::CONFIRM_LIFETIME) {
            return false;
        }

        $now = Time::now('UTC')->toDateTimeString();
        $this->db->table('newsletter_subscriptions')->where('id', $row['id'])->update([
            'status' => 'confirmed', 'confirm_token_hash' => null, 'confirmed_at' => $now, 'unsubscribed_at' => null, 'updated_at' => $now,
        ]);
        $this->audit->record('newsletter.confirmed', null, 'newsletter_subscription', (int) $row['id']);

        return true;
    }

    public static function unsubscribeSignature(int $id, string $email): string
    {
        return hash_hmac('sha256', 'unsubscribe|' . $id . '|' . mb_strtolower($email), FormGuard::secret());
    }

    /**
     * @param array<string, mixed> $subscription
     */
    public static function unsubscribeUrl(array $subscription): string
    {
        return site_url($subscription['locale'] . '/diaspora/newsletter/unsubscribe/' . $subscription['id'] . '/'
            . self::unsubscribeSignature((int) $subscription['id'], (string) $subscription['email']));
    }

    /**
     * @return array<string, mixed>|null The subscription, if the link is genuine
     */
    public function findForUnsubscribe(int $id, string $signature): ?array
    {
        $row = $this->db->table('newsletter_subscriptions')->where('id', $id)->get()->getRowArray();

        if ($row === null || ! hash_equals(self::unsubscribeSignature($id, (string) $row['email']), $signature)) {
            return null;
        }

        return $row;
    }

    public function unsubscribe(int $id, string $signature): bool
    {
        $row = $this->findForUnsubscribe($id, $signature);

        if ($row === null) {
            return false;
        }

        if ($row['status'] !== 'unsubscribed') {
            $now = Time::now('UTC')->toDateTimeString();
            $this->db->table('newsletter_subscriptions')->where('id', $id)->update([
                'status' => 'unsubscribed', 'confirm_token_hash' => null, 'unsubscribed_at' => $now, 'updated_at' => $now,
            ]);
            $this->audit->record('newsletter.unsubscribed', null, 'newsletter_subscription', $id);
        }

        return true;
    }

    /**
     * Queues a published diaspora newsletter article to every confirmed
     * subscriber. Each article is sent once.
     *
     * @param array<string, mixed> $article
     */
    public function sendIssue(array $article, int $userId): int
    {
        if ($article['category'] !== 'diaspora_newsletter' || $article['status'] !== 'published') {
            throw new InvalidArgumentException('Only a published diaspora newsletter can be sent.');
        }

        if ($this->wasSent((int) $article['id'])) {
            throw new InvalidArgumentException('This newsletter has already been sent.');
        }

        helper('site');
        $link        = site_url($article['locale'] . '/diaspora/stay-informed/' . $article['slug']);
        $subscribers = $this->db->table('newsletter_subscriptions')->where('status', 'confirmed')->get()->getResultArray();

        $this->db->transStart();
        foreach ($subscribers as $subscriber) {
            $body = trim((string) $article['summary']) . "\n\n"
                . lang('Forms.newsletterReadOnline', [$link]) . "\n\n"
                . lang('Forms.newsletterUnsubscribe', [self::unsubscribeUrl($subscriber)]) . "\n";
            $this->mail->queue('newsletter', (string) $subscriber['email'], (string) $article['title'], ltrim($body));
        }

        $this->db->table('newsletter_sends')->insert([
            'article_id'   => $article['id'],
            'queued_count' => count($subscribers),
            'sent_by'      => $userId,
            'created_at'   => Time::now('UTC')->toDateTimeString(),
        ]);
        $this->db->transComplete();

        $this->audit->record('newsletter.sent', $userId, 'articles', (int) $article['id'], ['recipients' => count($subscribers)]);

        return count($subscribers);
    }

    public function wasSent(int $articleId): bool
    {
        return $this->db->table('newsletter_sends')->where('article_id', $articleId)->countAllResults() > 0;
    }

    /**
     * @return array<string, int>
     */
    public function counts(): array
    {
        $counts = ['pending' => 0, 'confirmed' => 0, 'unsubscribed' => 0];
        foreach ($this->db->table('newsletter_subscriptions')->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $row) {
            $counts[$row['status']] = (int) $row['n'];
        }

        return $counts;
    }
}

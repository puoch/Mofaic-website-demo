<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\Newsletter;
use InvalidArgumentException;
use Tests\Support\StaffTestCase;

/**
 * Diaspora newsletter: separate opt-in, double opt-in (PD-09), unsubscribe
 * and sending an issue (DIA-09, DIA-10).
 */
final class NewsletterTest extends StaffTestCase
{
    public function testSubscribingQueuesAConfirmationAndStaysPending(): void
    {
        service('newsletter')->subscribe('Reader@Example.invalid', 'stay_informed', 'en');

        $row  = $this->db->table('newsletter_subscriptions')->get()->getRowArray();
        $mail = $this->db->table('mail_queue')->get()->getRowArray();

        $this->assertSame('reader@example.invalid', $row['email']);
        $this->assertSame('pending', $row['status']);
        $this->assertSame('reader@example.invalid', $mail['to_email']);
        $this->assertMatchesRegularExpression('#/en/diaspora/newsletter/confirm/[0-9a-f]{64}#', $mail['body']);
        $this->assertStringNotContainsString((string) $row['confirm_token_hash'], $mail['body'], 'Only the token, never its stored hash, is emailed.');
    }

    public function testConfirmingWithTheEmailedLink(): void
    {
        $token = $this->subscribeAndGetToken('reader@example.invalid');

        $this->assertFalse(service('newsletter')->confirm(str_repeat('0', 64)));
        $this->assertTrue(service('newsletter')->confirm($token));
        $this->assertFalse(service('newsletter')->confirm($token), 'A link works once.');
        $this->assertSame('confirmed', $this->db->table('newsletter_subscriptions')->get()->getRow()->status);
    }

    public function testExpiredLinksDoNotConfirm(): void
    {
        $token = $this->subscribeAndGetToken('reader@example.invalid');
        $this->db->table('newsletter_subscriptions')->update(['confirm_sent_at' => date('Y-m-d H:i:s', time() - Newsletter::CONFIRM_LIFETIME - 60)]);

        $this->assertFalse(service('newsletter')->confirm($token));
    }

    public function testRepeatedRequestsDoNotFloodTheAddress(): void
    {
        service('newsletter')->subscribe('reader@example.invalid', 'stay_informed', 'en');
        service('newsletter')->subscribe('reader@example.invalid', 'stay_informed', 'en');

        $this->assertSame(1, $this->db->table('mail_queue')->countAllResults());
        $this->assertSame(1, $this->db->table('newsletter_subscriptions')->countAllResults());
    }

    public function testUnsubscribeNeedsTheSignedLink(): void
    {
        service('newsletter')->confirm($this->subscribeAndGetToken('reader@example.invalid'));
        $row = $this->db->table('newsletter_subscriptions')->get()->getRowArray();

        $this->assertFalse(service('newsletter')->unsubscribe((int) $row['id'], str_repeat('a', 64)));
        $this->assertTrue(service('newsletter')->unsubscribe((int) $row['id'], Newsletter::unsubscribeSignature((int) $row['id'], $row['email'])));
        $this->assertSame('unsubscribed', $this->db->table('newsletter_subscriptions')->get()->getRow()->status);
    }

    public function testAnIssueGoesOnceToConfirmedSubscribersOnly(): void
    {
        service('newsletter')->confirm($this->subscribeAndGetToken('confirmed@example.invalid'));
        service('newsletter')->subscribe('pending@example.invalid', 'stay_informed', 'en');
        $this->db->table('mail_queue')->truncate();

        $staff   = $this->staff('ministryadmin');
        $article = $this->issue();

        $this->assertSame(1, service('newsletter')->sendIssue($article, (int) $staff->id));

        $mail = $this->db->table('mail_queue')->get()->getRowArray();
        $this->assertSame('confirmed@example.invalid', $mail['to_email']);
        $this->assertSame('Issue one', $mail['subject']);
        $this->assertMatchesRegularExpression('#/en/diaspora/newsletter/unsubscribe/\d+/[0-9a-f]{64}#', $mail['body']);
        $this->assertSame(1, $this->db->table('audit_events')->where('event', 'newsletter.sent')->countAllResults());

        $this->expectException(InvalidArgumentException::class);
        service('newsletter')->sendIssue($article, (int) $staff->id);
    }

    public function testOnlyPublishedNewslettersCanBeSent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        service('newsletter')->sendIssue(['id' => 1, 'category' => 'news', 'status' => 'published'] + $this->issue(), 1);
    }

    private function subscribeAndGetToken(string $email): string
    {
        service('newsletter')->subscribe($email, 'stay_informed', 'en');
        $body = (string) $this->db->table('mail_queue')->where('to_email', $email)->get()->getRow()->body;
        preg_match('#/confirm/([0-9a-f]{64})#', $body, $m);

        return $m[1];
    }

    /**
     * @return array<string, mixed>
     */
    private function issue(): array
    {
        $dept = $this->department('diaspora', true);
        $id   = $this->content('articles', ['category' => 'diaspora_newsletter', 'title' => 'Issue one', 'summary' => 'In this issue', 'department_id' => $dept]);

        return $this->db->table('articles')->where('id', $id)->get()->getRowArray();
    }
}

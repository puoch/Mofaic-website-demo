<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\SubmissionService;
use Tests\Support\StaffTestCase;

/**
 * Storing submissions, staff notifications and inbox handling (CON-03 to
 * CON-05, DIA-13, GOV-07, SEC-04).
 */
final class SubmissionServiceTest extends StaffTestCase
{
    private const PERSONAL = ['Amina Test Person', 'amina@example.invalid', '+000 1111 2222', 'Private message text'];

    public function testStoresWithAReferenceAndTheFirstStatus(): void
    {
        $saved = $this->inquiry();
        $row   = service('submissions')->find('inquiry', $saved['id']);

        $this->assertMatchesRegularExpression('/^INQ-\d{4}-[A-Z2-9]{6}$/', $saved['reference']);
        $this->assertSame('new', $row['status']);
        $this->assertNull($row['assigned_to']);
    }

    public function testNotificationsCarryNoPersonalDetails(): void
    {
        $this->rule('inquiry', "staff-one@example.invalid\nstaff-two@example.invalid", 'New inquiry {reference}');
        $this->rule('registration', 'diaspora-staff@example.invalid', 'Should not be used');

        $saved = $this->inquiry();
        $mails = $this->db->table('mail_queue')->get()->getResultArray();

        $this->assertCount(2, $mails);
        $this->assertSame(['staff-one@example.invalid', 'staff-two@example.invalid'], array_column($mails, 'to_email'));
        foreach ($mails as $mail) {
            $this->assertSame('New inquiry ' . $saved['reference'], $mail['subject']);
            $this->assertStringContainsString($saved['reference'], $mail['body']);
            $this->assertStringContainsString(site_url('admin/inquiries/inquiry/' . $saved['id']), $mail['body']);
            foreach (self::PERSONAL as $personal) {
                $this->assertStringNotContainsString($personal, $mail['subject'] . $mail['body']);
            }
        }
    }

    public function testInactiveRulesSendNothing(): void
    {
        $this->rule('inquiry', 'staff@example.invalid', 'Subject', false);
        $this->inquiry();

        $this->assertSame(0, $this->db->table('mail_queue')->countAllResults());
    }

    public function testAuditEntriesCarryNoPersonalDetails(): void
    {
        $saved = $this->inquiry();
        $staff = $this->staff('ministryadmin');
        service('submissions')->recordView('inquiry', $saved['id'], (int) $staff->id);
        service('submissions')->addNote('inquiry', $saved['id'], 'Staff note with private detail', (int) $staff->id);

        $events = $this->db->table('audit_events')->where('subject_type', 'inquiry')->get()->getResultArray();
        $this->assertSame(['submission.received', 'submission.viewed', 'submission.note-added'], array_column($events, 'event'));
        foreach ($events as $event) {
            foreach ([...self::PERSONAL, 'Staff note with private detail'] as $personal) {
                $this->assertStringNotContainsString($personal, (string) $event['details']);
            }
        }
    }

    public function testStatusAssignmentAndNotes(): void
    {
        $saved = $this->inquiry();
        $staff = $this->staff('ministryadmin');
        $service = service('submissions');

        $service->changeStatus('inquiry', $saved['id'], 'in_progress', (int) $staff->id);
        $service->assign('inquiry', $saved['id'], (int) $staff->id, (int) $staff->id);
        $service->addNote('inquiry', $saved['id'], 'Called back', (int) $staff->id);

        $row = $service->find('inquiry', $saved['id']);
        $this->assertSame('in_progress', $row['status']);
        $this->assertSame((int) $staff->id, (int) $row['assigned_to']);
        $this->assertSame(['Called back'], array_column($service->notes('inquiry', $saved['id']), 'note'));

        $this->expectException(\InvalidArgumentException::class);
        $service->changeStatus('inquiry', $saved['id'], 'deleted', (int) $staff->id);
    }

    public function testRecipientListKeepsOnlyValidAddresses(): void
    {
        $this->assertSame(['a@example.invalid', 'b@example.invalid'], SubmissionService::recipients("a@example.invalid, not-an-address\nb@example.invalid;a@example.invalid"));
    }

    /**
     * @return array{id: int, reference: string}
     */
    private function inquiry(): array
    {
        return service('submissions')->create('inquiry', [
            'name' => self::PERSONAL[0], 'email' => self::PERSONAL[1], 'phone' => self::PERSONAL[2], 'subject' => null, 'message' => self::PERSONAL[3],
        ], 'en');
    }

    private function rule(string $type, string $recipients, string $subject, bool $active = true): void
    {
        $this->db->table('notification_rules')->insert([
            'submission_type' => $type, 'recipients' => $recipients, 'subject' => $subject, 'intro' => 'Hello.', 'is_active' => $active ? 1 : 0,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Config\Factories;
use Config\Hosting;
use Config\Media;
use Tests\Support\StaffTestCase;

/**
 * Settings for hosting without cron or large files (Config\Hosting,
 * docs/infinityfree.md). All are off by default.
 */
final class HostingTest extends StaffTestCase
{
    private function hosting(bool $sendMailInRequest): void
    {
        $config                    = new Hosting();
        $config->sendMailInRequest = $sendMailInRequest;
        $config->mailPerRequest    = 2;
        Factories::injectMock('config', 'Hosting', $config);
    }

    /**
     * Messages a send was tried for. Tests have no SMTP relay, so the attempt
     * itself fails; what matters is that it happened without cron.
     */
    private function sent(): int
    {
        return $this->db->table('mail_queue')->where('attempts >', 0)->countAllResults();
    }

    public function testMailWaitsForCronByDefault(): void
    {
        service('mailQueue')->queue('notification', 'staff@example.invalid', 'New inquiry', 'Body');

        $this->assertSame(0, $this->sent());
    }

    public function testWithoutCronSingleMessagesGoOutWhenQueued(): void
    {
        $this->hosting(true);

        service('mailQueue')->queue('notification', 'staff@example.invalid', 'New inquiry', 'Body');
        service('mailQueue')->queue('newsletter', 'reader@example.invalid', 'Issue', 'Body');

        $this->assertSame(1, $this->sent(), 'A newsletter issue is not sent inside the request that queues it.');
        $this->assertSame(1, $this->db->table('mail_queue')->where('attempts', 0)->where('purpose', 'newsletter')->countAllResults());
    }

    public function testWithoutCronStaffPagesSendWaitingMail(): void
    {
        foreach (range(1, 3) as $i) {
            service('mailQueue')->queue('newsletter', "reader{$i}@example.invalid", 'Issue', 'Body');
        }
        $this->hosting(true);

        $this->actingAs($this->staff('ministryadmin'))->get('admin')->assertOK();

        $this->assertSame(2, $this->sent(), 'At most mailPerRequest messages per page.');
    }

    public function testUploadCapLowersEveryKindToTheHostLimit(): void
    {
        $this->assertSame(200 * 1024, (new Media())->kinds['video']['maxKb']);

        $_SERVER['hosting.maxUploadMegabytes'] = '10';

        try {
            $media = new Media();
        } finally {
            unset($_SERVER['hosting.maxUploadMegabytes']);
        }

        foreach ($media->kinds as $kind => $rules) {
            $this->assertLessThanOrEqual(10 * 1024, $rules['maxKb'], $kind);
        }
        $this->assertSame(10 * 1024, $media->kinds['image']['maxKb']);
    }
}

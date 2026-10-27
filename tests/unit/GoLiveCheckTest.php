<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Database\Seeds\DemoSeeder;
use App\Database\Seeds\StructureSeeder;
use App\Libraries\GoLiveCheck;
use Config\GoLive;
use Tests\Support\StaffTestCase;

/**
 * The go-live check must list every open decision and never report an
 * unsettled one as done (instruction: unresolved policy is never silently
 * treated as approved).
 */
final class GoLiveCheckTest extends StaffTestCase
{
    private string $backups;

    protected function setUp(): void
    {
        parent::setUp();
        $this->backups = sys_get_temp_dir() . '/mofaic-golive-' . bin2hex(random_bytes(4));
        mkdir($this->backups);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->backups . '/*/MANIFEST') ?: [] as $file) {
            unlink($file);
            rmdir(dirname($file));
        }
        rmdir($this->backups);
        parent::tearDown();
    }

    /**
     * @return array<string, array{area: string, check: string, status: string, detail: string}>
     */
    private function results(?GoLive $config = null, ?int $composerJsonStatus = 404): array
    {
        $config ??= new GoLive();
        $config->backupDirectory = $this->backups;

        // No real web request: answer as the web server would.
        $check = new class ($config, $composerJsonStatus) extends GoLiveCheck {
            public function __construct(GoLive $config, private readonly ?int $status)
            {
                parent::__construct($config);
            }

            protected function httpStatus(string $url): ?int
            {
                return $this->status;
            }
        };

        $results = [];

        foreach ($check->run() as $r) {
            $results[$r['check']] = $r;
        }

        return $results;
    }

    public function testReadsEveryProposalAndDeferredItemFromTheDecisionLog(): void
    {
        $check = new GoLiveCheck(new GoLive());
        $log   = (string) file_get_contents(ROOTPATH . 'docs/decision-log.md');

        $proposals = $check->proposalsInUse($log);
        foreach (['PD-01', 'PD-11', 'G3-05', 'G4-07', 'G2-02'] as $id) {
            $this->assertArrayHasKey($id, $proposals);
        }
        // Confirmed items are not in the "in use" table.
        $this->assertArrayNotHasKey('PD-02', $proposals);
        $this->assertArrayNotHasKey('PD-14', $proposals);

        $this->assertSame(range(1, 15), array_keys($check->deferredItems($log)));
    }

    public function testUnsettledDecisionsBlockTheLaunch(): void
    {
        $config                    = new GoLive();
        $config->approvedProposals = [];

        $results = $this->results($config);

        $this->assertSame('fail', $results['PD-05 approved by MOFAIC']['status']);
        $this->assertSame('fail', $results['Deferred item 13 settled']['status']);
    }

    public function testADecisionStopsBlockingOnlyWithItsRecordedSource(): void
    {
        $config                        = new GoLive();
        $config->approvedProposals     = ['PD-05' => 'Test decision record'];
        $config->resolvedDeferredItems = [13 => 'Test retention schedule'];

        $results = $this->results($config);

        $this->assertSame('pass', $results['PD-05 approved by MOFAIC']['status']);
        $this->assertSame('pass', $results['Deferred item 13 settled']['status']);
        $this->assertSame('fail', $results['PD-06 approved by MOFAIC']['status']);
    }

    public function testShippedApprovalsMatchTheDecisionLog(): void
    {
        $log      = (string) file_get_contents(ROOTPATH . 'docs/decision-log.md');
        $approved = (new GoLive())->approvedProposals;

        // Every approval names a real proposal and where it was decided.
        foreach ($approved as $id => $source) {
            $this->assertMatchesRegularExpression('/\\b' . preg_quote($id, '/') . '\\b/', $log, "{$id} is not in the decision log.");
            $this->assertNotSame('', trim($source), "{$id} has no recorded source.");
        }

        $this->assertSame('pass', $this->results()['PD-05 approved by MOFAIC']['status']);
    }

    public function testWarnsWhenBootstrapComesFromTheCdn(): void
    {
        $this->assertSame('pass', $this->results()['Bootstrap is served from this server']['status']);

        $assets                  = new \Config\Assets();
        $assets->bootstrapSource = 'cdn';
        \CodeIgniter\Config\Factories::injectMock('config', 'Assets', $assets);

        $this->assertSame('warn', $this->results()['Bootstrap is served from this server']['status']);
    }

    public function testFailsWhenTheWebServerServesProjectFiles(): void
    {
        $check = 'Project files are not served by the web server';

        $this->assertSame('pass', $this->results(null, 404)[$check]['status']);
        $this->assertSame('fail', $this->results(null, 200)[$check]['status']);
        $this->assertSame('warn', $this->results(null, null)[$check]['status']);
    }

    public function testTheTestEnvironmentIsNotReady(): void
    {
        $results = $this->results();

        $this->assertSame('fail', $results['CI_ENVIRONMENT is production']['status']);
        $this->assertSame('fail', $results['Every request is forced to https']['status']);
        $this->assertSame('pass', $results['Content Security Policy is on']['status']);
    }

    public function testFindsDemonstrationData(): void
    {
        $this->assertSame('pass', $this->results()['No demonstration data']['status']);

        $this->seed(DemoSeeder::class);

        $result = $this->results()['No demonstration data'];
        $this->assertSame('fail', $result['status']);
        $this->assertStringContainsString('in missions', $result['detail']);
        $this->assertStringContainsString('in staff accounts', $result['detail']);
    }

    public function testRequiresMinistryTextInTheFormBlocks(): void
    {
        $this->seed(StructureSeeder::class);
        $this->assertSame('fail', $this->results()['Ministry-approved text in "privacy_notice"']['status']);

        $this->block('privacy_notice', '<p>Approved test text.</p>');
        $this->assertSame('pass', $this->results()['Ministry-approved text in "privacy_notice"']['status']);

        $this->block('privacy_notice', '<p>Draft (' . DemoSeeder::LABEL . ')</p>');
        $this->assertSame('fail', $this->results()['Ministry-approved text in "privacy_notice"']['status']);
    }

    public function testNeedsARecentCompleteBackup(): void
    {
        $check = 'A complete backup from the last 26 hours';
        $this->assertSame('fail', $this->results()[$check]['status']);

        mkdir($this->backups . '/20261027T000000Z');
        touch($this->backups . '/20261027T000000Z/MANIFEST', time() - 30 * 3600);
        $this->assertSame('fail', $this->results()[$check]['status']);

        touch($this->backups . '/20261027T000000Z/MANIFEST');
        $this->assertSame('pass', $this->results()[$check]['status']);
    }

    public function testCountsSuperAdministratorsWhoCanSignIn(): void
    {
        $check = 'At least 2 active Super Administrators';
        $this->staff('superadmin');
        $banned = $this->staff('superadmin');
        $banned->ban();

        $result = $this->results()[$check];
        $this->assertSame('warn', $result['status']);
        $this->assertSame('1 now', $result['detail']);

        $this->staff('superadmin');
        $this->assertSame('pass', $this->results()[$check]['status']);
    }
}

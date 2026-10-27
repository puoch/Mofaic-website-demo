<?php

declare(strict_types=1);

namespace Tests\Feature;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

final class HealthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    public function testHealthReportsOkWithoutRevealingDetails(): void
    {
        $result = $this->get('health');

        $result->assertOK();
        $this->assertSame(['status' => 'ok', 'database' => 'ok'], json_decode($result->getJSON(), true));
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));
    }
}

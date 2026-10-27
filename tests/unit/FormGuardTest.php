<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FormGuard;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use Config\App;
use Config\Submissions;
use Tests\Support\StaffTestCase;

/**
 * Spam protection without outside services (PD-11, SEC-02).
 */
final class FormGuardTest extends StaffTestCase
{
    public function testAcceptsAFormFilledInAtHumanSpeed(): void
    {
        $this->assertNull($this->guard()->check($this->request(['form_token' => FormGuard::token('inquiry', time() - 30)]), 'inquiry'));
    }

    public function testRejectsAFilledHiddenField(): void
    {
        $request = $this->request(['form_token' => FormGuard::token('inquiry', time() - 30), 'website_url' => 'http://spam.example.invalid']);
        $this->assertSame(lang('Forms.notSent'), $this->guard()->check($request, 'inquiry'));
    }

    public function testRejectsFormsSentTooQuicklyOrTooLate(): void
    {
        $this->assertSame(lang('Forms.tooFast'), $this->guard()->check($this->request(['form_token' => FormGuard::token('inquiry', time())]), 'inquiry'));
        $this->assertSame(lang('Forms.expired'), $this->guard()->check($this->request(['form_token' => FormGuard::token('inquiry', time() - 3 * HOUR)]), 'inquiry'));
    }

    public function testRejectsMissingTamperedOrBorrowedTokens(): void
    {
        $valid = FormGuard::token('inquiry', time() - 30);

        $this->assertSame(lang('Forms.expired'), $this->guard()->check($this->request([]), 'inquiry'));
        $this->assertSame(lang('Forms.expired'), $this->guard()->check($this->request(['form_token' => substr($valid, 0, -1) . '0']), 'inquiry'));
        $this->assertSame(lang('Forms.expired'), $this->guard()->check($this->request(['form_token' => $valid]), 'registration'), 'A token is valid for one form only.');
    }

    public function testLimitsSubmissionsPerAddressPerHour(): void
    {
        $limit = config(Submissions::class)->submissionsPerHour;

        for ($i = 0; $i < $limit; $i++) {
            $this->assertNull($this->guard()->check($this->request(['form_token' => FormGuard::token('contribution', time() - 30)]), 'contribution'));
        }

        $this->assertSame(lang('Forms.tooMany'), $this->guard()->check($this->request(['form_token' => FormGuard::token('contribution', time() - 30)]), 'contribution'));
    }

    private function guard(): FormGuard
    {
        return new FormGuard(config(Submissions::class));
    }

    /**
     * @param array<string, string> $post
     */
    private function request(array $post): IncomingRequest
    {
        $config  = config(App::class);
        $request = new IncomingRequest($config, new SiteURI($config), null, new UserAgent());
        $request->setMethod('POST');
        $request->setGlobal('post', $post);

        return $request;
    }
}

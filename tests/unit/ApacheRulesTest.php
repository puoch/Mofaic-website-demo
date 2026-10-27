<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Apache reads these files on shared hosting (docs/shared-hosting.md). The
 * Nginx setup in docs/deployment.md does the same in its server block.
 */
final class ApacheRulesTest extends CIUnitTestCase
{
    public function testTheProjectRootSendsEveryRequestIntoPublic(): void
    {
        $rules = (string) file_get_contents(ROOTPATH . '.htaccess');

        $this->assertStringContainsString('RewriteRule ^(.*)$ public/$1 [L]', $rules);
        $this->assertStringContainsString('<IfModule !mod_rewrite.c>', $rules, 'Without mod_rewrite nothing may be served.');
    }

    public function testTheFrontControllerDoesNotNeedPathInfo(): void
    {
        $rules = (string) file_get_contents(FCPATH . '.htaccess');

        // InfinityFree answers 404 for index.php/<path>; the path comes from REQUEST_URI.
        $this->assertStringContainsString('RewriteRule ^ index.php [L,QSA]', $rules);
        $this->assertStringNotContainsString('index.php/$1', $rules);
        $this->assertSame('REQUEST_URI', config('App')->uriProtocol);
    }

    public function testUploadedFilesCannotRunAsCode(): void
    {
        $rules = (string) file_get_contents(FCPATH . 'uploads/.htaccess');

        $this->assertMatchesRegularExpression('/<FilesMatch "[^"]*php[^"]*">\s*<IfModule authz_core_module>\s*Require all denied/', $rules);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Assets;
use Config\ContentSecurityPolicy;

/**
 * Every stylesheet and script the layouts load must be in the repository and
 * can be served from this server (PD-17, on-premises requirement). The CDN
 * mode must keep integrity checks and the local fallback. Until Gate 5 the
 * Bootstrap files were missing from git because a Composer ignore rule also
 * matched public/assets/vendor.
 */
final class LocalAssetsTest extends CIUnitTestCase
{
    public function testEveryAssetTheLayoutsLoadExists(): void
    {
        $assets = [];

        foreach (glob(APPPATH . 'Views/{layouts,partials}/*.php', GLOB_BRACE) as $view) {
            preg_match_all("/base_url\\('(assets\\/[^']+)'(?: \\. \\\$bootstrapCss)?\\)/", (string) file_get_contents($view), $m);
            $assets = array_merge($assets, $m[1]);
        }

        $this->assertNotSame([], $assets);

        // Bootstrap is loaded through bootstrap_css() and bootstrap_js().
        $config = config(Assets::class);
        foreach (array_keys($config->integrity) as $file) {
            $assets[] = 'assets/vendor/bootstrap-' . $config->bootstrapVersion . '/' . $file;
        }
        $assets[] = 'assets/js/cdn-fallback.js';

        foreach (array_unique($assets) as $asset) {
            $this->assertFileExists(FCPATH . $asset);
        }
    }

    public function testLocalModeServesBootstrapFromThisServer(): void
    {
        $tags = bootstrap_css('ltr') . bootstrap_css('rtl') . bootstrap_js();

        $this->assertStringContainsString(base_url('assets/vendor/bootstrap-5.3.8/css/bootstrap.min.css'), $tags);
        $this->assertStringContainsString(base_url('assets/vendor/bootstrap-5.3.8/css/bootstrap.rtl.min.css'), $tags);
        $this->assertStringContainsString(base_url('assets/vendor/bootstrap-5.3.8/js/bootstrap.bundle.min.js'), $tags);
        $this->assertStringNotContainsString('jsdelivr', $tags);
        $this->assertStringNotContainsString('cdn-fallback', $tags);
    }

    public function testCdnModeChecksIntegrityAndFallsBackToTheLocalCopy(): void
    {
        $config                  = new Assets();
        $config->bootstrapSource = 'cdn';
        Factories::injectMock('config', 'Assets', $config);

        $css = bootstrap_css('ltr');
        $js  = bootstrap_js();

        $this->assertStringContainsString('href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"', $css);
        $this->assertStringContainsString('data-local="' . base_url('assets/vendor/bootstrap-5.3.8/css/bootstrap.min.css') . '"', $css);
        $this->assertStringContainsString('crossorigin="anonymous"', $js);
        $this->assertStringContainsString(base_url('assets/js/cdn-fallback.js'), $js);

        // Each integrity value must match the vendored file, or the browser
        // would refuse the CDN copy on every page and always fall back.
        foreach ($config->integrity as $file => $integrity) {
            $hash = 'sha384-' . base64_encode(hash_file('sha384', FCPATH . 'assets/vendor/bootstrap-5.3.8/' . $file, true));
            $this->assertSame($hash, $integrity, $file);
        }
    }

    public function testContentSecurityPolicyAllowsTheCdnOnlyInCdnMode(): void
    {
        $this->assertSame('self', (new ContentSecurityPolicy())->scriptSrc);

        $_SERVER['assets.bootstrapSource'] = 'cdn';

        try {
            $csp = new ContentSecurityPolicy();
        } finally {
            unset($_SERVER['assets.bootstrapSource']);
        }

        foreach (['scriptSrc', 'scriptSrcElem', 'styleSrc', 'styleSrcElem'] as $directive) {
            $this->assertSame(['self', 'https://cdn.jsdelivr.net'], $csp->{$directive}, $directive);
        }
        $this->assertSame('self', $csp->imageSrc);
    }

    public function testVendoredBootstrapMatchesItsChecksums(): void
    {
        $dir  = FCPATH . 'assets/vendor/bootstrap-5.3.8/';
        $sums = file($dir . 'SHA256SUMS', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $this->assertNotFalse($sums);
        $this->assertCount(3, $sums);

        foreach ($sums as $line) {
            [$hash, $file] = preg_split('/\s+/', trim($line), 2);
            $this->assertSame($hash, hash_file('sha256', $dir . $file), $file);
        }
    }

    public function testNoViewLoadsScriptsOrStylesFromOtherHosts(): void
    {
        $views = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(APPPATH . 'Views', \FilesystemIterator::SKIP_DOTS));

        foreach ($views as $view) {
            $this->assertDoesNotMatchRegularExpression('/<(?:script|link)[^>]+(?:src|href)="(?:https?:)?\/\//i', (string) file_get_contents((string) $view), (string) $view);
        }
    }
}

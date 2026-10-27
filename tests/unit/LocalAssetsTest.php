<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Every stylesheet and script the layouts load must be in the repository and
 * served from this server (PD-17, on-premises requirement). Until Gate 5 the
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

        foreach (array_unique($assets) as $asset) {
            if (str_ends_with($asset, '/')) {
                foreach (['bootstrap.min.css', 'bootstrap.rtl.min.css'] as $css) {
                    $this->assertFileExists(FCPATH . $asset . $css);
                }

                continue;
            }

            $this->assertFileExists(FCPATH . $asset);
        }
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

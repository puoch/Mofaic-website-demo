<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * The codebase must carry no placeholder text from another government's site
 * (Kenyan placeholder text and Serbian reference material were named in the
 * project instructions) and no invented Ministry data.
 *
 * This scans the repository only. Content that staff enter is not scanned:
 * real pages may rightly mention Kenya or Serbia (for example a mission's
 * coverage), so reviewing it is part of the content sign-off.
 */
final class ContentSafetyTest extends CIUnitTestCase
{
    /**
     * Words that would only appear if text was copied from those sites.
     * Matched as whole words, ignoring case.
     */
    private const TERMS = [
        // Kenya
        'Kenya', 'Kenyan', 'Nairobi', 'Mombasa', 'Kisumu', 'Harambee', 'Ksh', 'KES', 'shillings?', 'go\.ke', '\+254',
        // Serbia
        'Serbia', 'Serbian', 'Srbija', 'Srbije', 'Republika', 'Belgrade', 'Beograd', 'Kneza', 'dinars?', 'RSD', 'gov\.rs', 'mfa\.rs', '\+381',
    ];

    /**
     * Files that must name these words: the framework's list of country
     * domains, this test, and the sponsor's embassies list (South Sudan has
     * an embassy in Nairobi; MissionsAbroadSeeder names its source document).
     */
    private const ALLOWED = ['app/Config/Hostnames.php', 'tests/unit/ContentSafetyTest.php', 'app/Database/Seeds/MissionsAbroadSeeder.php'];

    private static function pattern(): string
    {
        // Lookarounds instead of \b, so terms starting with "+" match too.
        return '/(?<!\w)(?:' . implode('|', self::TERMS) . ')(?!\w)/i';
    }

    /**
     * @return list<string> repository-relative paths of text files we wrote
     */
    private function files(): array
    {
        $files = [];

        foreach (['app', 'public', 'docs', 'tests', 'scripts', '.github'] as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOTPATH . $dir, RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                $path = str_replace(ROOTPATH, '', (string) $file);

                if (preg_match('#(^|/)(node_modules|report|vendor|uploads)/|\.(ico|png|jpe?g|webp|gif|pdf|mp3|mp4|db)$|\.min\.(css|js)$#', $path) === 1 || in_array($path, self::ALLOWED, true)) {
                    continue;
                }

                $files[] = $path;
            }
        }

        return [...$files, 'README.md', 'env', 'composer.json'];
    }

    public function testNoTextFromOtherGovernmentSites(): void
    {
        $pattern = self::pattern();
        $found   = [];
        $files   = $this->files();

        $this->assertGreaterThan(100, count($files), 'the scan found too few files to be meaningful');

        foreach ($files as $path) {
            foreach (file(ROOTPATH . $path) ?: [] as $number => $line) {
                if (preg_match($pattern, $line, $m) === 1) {
                    $found[] = $path . ':' . ($number + 1) . ' "' . $m[0] . '"';
                }
            }
        }

        $this->assertSame([], $found);
    }

    public function testNoCyrillicOrSerbianLatinLetters(): void
    {
        // English is the only launch language; these letters would point to
        // copied Serbian text.
        $found = [];

        foreach ($this->files() as $path) {
            if (preg_match('/[\p{Cyrillic}đĐćĆ]/u', (string) file_get_contents(ROOTPATH . $path)) === 1) {
                $found[] = $path;
            }
        }

        $this->assertSame([], $found);
    }

    public function testTheScanWouldNoticeCopiedText(): void
    {
        $pattern = self::pattern();

        $this->assertMatchesRegularExpression($pattern, 'Tel: +254 20 000 0000');
        $this->assertMatchesRegularExpression($pattern, 'Embassy of the Republic of Kenya, Nairobi');
        $this->assertMatchesRegularExpression($pattern, 'Ministry of Foreign Affairs, Kneza Milosa 24, Beograd');
        $this->assertDoesNotMatchRegularExpression($pattern, 'Ordinary news about the Ministry');
    }
}

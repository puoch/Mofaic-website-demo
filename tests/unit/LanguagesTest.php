<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\Languages;

final class LanguagesTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Factories::reset('config');
    }

    public function testEnglishIsTheOnlyLaunchLanguage(): void
    {
        $this->assertSame(['en'], config(Languages::class)->enabledCodes());
        $this->assertSame(['en'], config(App::class)->supportedLocales);
        $this->assertSame('en', config(App::class)->defaultLocale);
    }

    public function testEnglishIsLeftToRight(): void
    {
        $this->assertSame('ltr', site_dir('en'));
    }

    public function testRightToLeftLanguageCanBeAddedByConfiguration(): void
    {
        // Placeholder code "xx" stands for any future right-to-left language.
        config(Languages::class)->languages['xx'] = ['name' => 'Test', 'nativeName' => 'Test', 'dir' => 'rtl', 'enabled' => true];

        $this->assertSame('rtl', site_dir('xx'));
    }

    public function testUnknownLanguageDefaultsToLeftToRight(): void
    {
        $this->assertSame('ltr', site_dir('zz'));
    }

    public function testEverySupportedLocaleHasALanguageEntry(): void
    {
        foreach (config(App::class)->supportedLocales as $locale) {
            $this->assertArrayHasKey($locale, config(Languages::class)->languages);
        }
    }
}

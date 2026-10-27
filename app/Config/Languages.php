<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Public website languages.
 *
 * Confirmed 26 Oct 2026: English is the only launch language. The structure
 * supports more languages, including right-to-left ones, without code changes:
 * add an entry here, add the code to App::$supportedLocales, and supply
 * Ministry-approved translations in app/Language/<code>/.
 *
 * What a visitor sees when a page has no translation is still undecided
 * (brief section 6, item 1).
 */
class Languages extends BaseConfig
{
    /**
     * @var array<string, array{name: string, nativeName: string, dir: 'ltr'|'rtl', enabled: bool}>
     */
    public array $languages = [
        'en' => [
            'name'       => 'English',
            'nativeName' => 'English',
            'dir'        => 'ltr',
            'enabled'    => true,
        ],
    ];

    /**
     * @return 'ltr'|'rtl'
     */
    public function directionFor(string $locale): string
    {
        return ($this->languages[$locale]['dir'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr';
    }

    /**
     * @return list<string>
     */
    public function enabledCodes(): array
    {
        return array_keys(array_filter($this->languages, static fn (array $l): bool => $l['enabled']));
    }
}

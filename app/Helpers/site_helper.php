<?php

declare(strict_types=1);

use Config\Languages;
use Config\Navigation;

if (! function_exists('site_locale')) {
    function site_locale(): string
    {
        return service('request')->getLocale();
    }
}

if (! function_exists('site_dir')) {
    /**
     * Text direction for the current locale: 'ltr' or 'rtl'.
     */
    function site_dir(?string $locale = null): string
    {
        return config(Languages::class)->directionFor($locale ?? site_locale());
    }
}

if (! function_exists('locale_url')) {
    /**
     * URL of a public page in the current (or given) locale.
     */
    function locale_url(string $path = '', ?string $locale = null): string
    {
        $path = trim($path, '/');

        return site_url(($locale ?? site_locale()) . ($path === '' ? '' : '/' . $path));
    }
}

if (! function_exists('primary_navigation')) {
    /**
     * Top navigation items in approved order, with ARCHIVES inserted only when
     * configuration places it there.
     *
     * @return list<array<string, mixed>>
     */
    function primary_navigation(): array
    {
        $config = config(Navigation::class);
        $items  = [];

        foreach ($config->primary as $item) {
            $items[] = $item;

            if ($config->archivesPlacement === 'primary' && $item['key'] === $config->archivesAfter) {
                $items[] = ['key' => 'archives', 'path' => $config->archivesPath];
            }
        }

        return $items;
    }
}

if (! function_exists('nav_is_active')) {
    function nav_is_active(string $path): bool
    {
        $current = trim(service('request')->getPath(), '/');
        $target  = trim(site_locale() . '/' . $path, '/');

        if ($path === '') {
            return $current === $target;
        }

        return $current === $target || str_starts_with($current, $target . '/');
    }
}

if (! function_exists('parse_external_links')) {
    /**
     * Reads "Label | https://address" lines entered by editors. Lines without
     * a valid http(s) address are ignored.
     *
     * @return list<array{label: string, url: string}>
     */
    function parse_external_links(?string $text): array
    {
        $links = [];

        foreach (preg_split('/\R/', (string) $text) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));

            if (count($parts) !== 2 || $parts[0] === '') {
                continue;
            }

            if (preg_match('#^https?://#i', $parts[1]) !== 1 || filter_var($parts[1], FILTER_VALIDATE_URL) === false) {
                continue;
            }

            $links[] = ['label' => $parts[0], 'url' => $parts[1]];
        }

        return $links;
    }
}

if (! function_exists('format_date')) {
    function format_date(?string $datetime): string
    {
        if ($datetime === null || $datetime === '') {
            return '';
        }

        return \CodeIgniter\I18n\Time::parse($datetime, 'UTC')->toLocalizedString('d MMMM yyyy');
    }
}

if (! function_exists('article_url')) {
    /**
     * @param array<string, mixed> $article
     */
    function article_url(array $article): string
    {
        $segment = match ($article['category']) {
            'statement'        => 'statements',
            'mission_activity' => 'mission-activities',
            default            => 'news',
        };

        return locale_url('news-press/' . $segment . '/' . $article['slug']);
    }
}

if (! function_exists('mission_url')) {
    /**
     * @param array<string, mixed> $mission
     */
    function mission_url(array $mission): string
    {
        return locale_url('missions/profile/' . $mission['slug']);
    }
}

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

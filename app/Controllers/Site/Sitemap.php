<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use Config\Navigation;

/**
 * Sitemap, linked from the footer bottom bar. Lists the approved navigation
 * (Config\Navigation) and the footer pages, so it never needs hand editing.
 */
class Sitemap extends SiteController
{
    public function index(): string
    {
        return view('site/sitemap', [
            'primary' => primary_navigation(),
            'footer'  => config(Navigation::class),
        ]);
    }
}

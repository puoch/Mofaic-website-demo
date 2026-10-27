<?php

declare(strict_types=1);

namespace App\Controllers\Site;

/**
 * Sections whose modules come at a later gate (SSD DIASPORA, Gate 4). They
 * show an empty state so the approved navigation still works.
 */
class Placeholder extends SiteController
{
    public function section(string $navKey): string
    {
        return view('site/placeholder', ['heading' => lang('Site.nav.' . $navKey)]);
    }
}

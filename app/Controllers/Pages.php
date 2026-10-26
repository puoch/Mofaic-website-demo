<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Public pages. At Gate 2 every section shows an empty state; Gate 3 replaces
 * these with the content modules.
 */
class Pages extends BaseController
{
    public function home(): string
    {
        return view('pages/home');
    }

    public function section(string $navKey): string
    {
        $heading = lang('Site.nav.' . $navKey);

        if ($heading === 'Site.nav.' . $navKey) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view('pages/section', ['heading' => $heading]);
    }
}

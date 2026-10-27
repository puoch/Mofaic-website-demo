<?php

declare(strict_types=1);

namespace App\Controllers\Site;

/**
 * CONTACTS: official contact information entered by Ministry
 * Administrators (CON-01). Nothing is shown until that text exists. The
 * public inquiry form arrives with Gate 4.
 */
class Contacts extends SiteController
{
    public function index(): string
    {
        $block = db_connect()->table('site_blocks')
            ->where(['block_key' => 'contact_information', 'locale' => $this->locale()])
            ->get()
            ->getRowArray();

        return view('site/contacts', [
            'heading'     => lang('Site.nav.contacts'),
            'contactHtml' => service('markdown')->toHtml($block['body'] ?? null),
        ]);
    }
}

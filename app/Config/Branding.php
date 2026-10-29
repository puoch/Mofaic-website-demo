<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * National emblem shown in the header and footer. Colours and fonts are the
 * tokens at the top of public/assets/css/site.css. Replace both when the
 * Ministry supplies its official brand files.
 */
class Branding extends BaseConfig
{
    /**
     * Emblem image under public/, or '' to show none. The coat of arms of
     * the Republic of South Sudan; see public/assets/brand/README.md.
     */
    public string $emblem = 'assets/brand/coat-of-arms.svg';
}

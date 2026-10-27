<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Where browsers fetch Bootstrap from (PD-17).
 *
 * 'local' serves the copy in public/assets/vendor from this server. Use it on
 * the MOFAIC on-premises server. 'cdn' loads the same files from jsDelivr with
 * Subresource Integrity, for the VPS phase; if the CDN cannot be reached or a
 * file does not match its hash, assets/js/cdn-fallback.js loads the local copy.
 *
 * Set per server in .env: assets.bootstrapSource = cdn
 */
class Assets extends BaseConfig
{
    /** 'local' or 'cdn'. Anything else counts as 'local'. */
    public string $bootstrapSource = 'local';

    public string $bootstrapVersion = '5.3.8';

    /** Base address of the npm package's dist folder on the CDN, ending in /. */
    public string $cdnBase = 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/';

    /**
     * SHA-384 of each file, taken from the npm package bootstrap@5.3.8, which
     * the vendored copy matches byte for byte (see SHA256SUMS). A browser
     * refuses a CDN file that does not match.
     *
     * @var array<string, string> path under dist/ => integrity value
     */
    public array $integrity = [
        'css/bootstrap.min.css'      => 'sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB',
        'css/bootstrap.rtl.min.css'  => 'sha384-CfCrinSRH2IR6a4e6fy2q6ioOX7O6Mtm1L9vRvFZ1trBncWmMePhzvafv7oIcWiW',
        'js/bootstrap.bundle.min.js' => 'sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI',
    ];

    public function usesCdn(): bool
    {
        return $this->bootstrapSource === 'cdn';
    }

    /** Scheme and host of the CDN, for the Content Security Policy. */
    public function cdnOrigin(): string
    {
        $url = parse_url($this->cdnBase) ?: [];

        return ($url['scheme'] ?? 'https') . '://' . ($url['host'] ?? '');
    }
}

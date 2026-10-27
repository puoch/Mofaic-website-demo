<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Settings for hosting without a command line or cron, such as the
 * InfinityFree test copy (docs/infinityfree.md). Every setting here is off by
 * default, and none belongs on the VPS or the on-premises server.
 *
 * Set per server in .env, for example hosting.sendMailInRequest = true
 */
class Hosting extends BaseConfig
{
    /**
     * Opens /maintenance, a page that runs database migrations, adds missing
     * structure pages, creates staff accounts and shows the go-live check,
     * for hosts without SSH. Empty keeps the page switched off (404). Use at
     * least 32 random characters, and remove it again when you are done.
     */
    public string $maintenanceToken = '';

    /**
     * Without cron, nothing runs `php spark mail:send` (PD-10). When true, a
     * message is sent as soon as it is queued, and each staff page sends up to
     * $mailPerRequest waiting messages (retries, newsletter issues).
     */
    public bool $sendMailInRequest = false;

    public int $mailPerRequest = 10;

    /**
     * Largest upload in megabytes, whatever Config\Media allows for the kind
     * (PD-12). 0 means no extra limit. InfinityFree refuses files over 10 MB.
     */
    public int $maxUploadMegabytes = 0;

    public function maintenanceEnabled(): bool
    {
        return strlen($this->maintenanceToken) >= 32;
    }
}

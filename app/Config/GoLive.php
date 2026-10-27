<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Settings for `php spark golive:check`, the list of what still blocks the
 * live launch.
 *
 * Proposals and deferred items come from docs/decision-log.md. An item stops
 * blocking only when it is listed below with the decision that settled it.
 * Never add an item here without a recorded Ministry decision.
 */
class GoLive extends BaseConfig
{
    /**
     * Proposal IDs from the decision log ("Proposed, in use") that MOFAIC has
     * approved, with where the approval is recorded.
     *
     * @var array<string, string> ID => source, for example 'PD-05' => 'Ministry memo of 2026-11-02'
     */
    public array $approvedProposals = [];

    /**
     * Deferred item numbers from the decision log that MOFAIC has settled.
     *
     * @var array<int, string> number => source
     */
    public array $resolvedDeferredItems = [];

    /** Folder that scripts/backup.sh writes to. */
    public string $backupDirectory = '/var/backups/mofaic-web';

    /** A backup older than this counts as missing. */
    public int $maxBackupAgeHours = 26;

    /** Fewer active Super Administrators than this leaves nobody to cover absences. */
    public int $minimumSuperAdmins = 2;
}

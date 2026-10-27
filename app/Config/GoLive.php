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
     * Proposal IDs from the decision log ("Proposed, in use") that the sponsor
     * or MOFAIC has approved, with where the approval is recorded.
     *
     * @var array<string, string> ID => source, for example 'PD-05' => 'Ministry memo of 2026-11-02'
     */
    public array $approvedProposals = [
        'PD-01' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-03' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-04' => 'Sponsor, proposal sheet 2026-10-27, interim until deferred item 2',
        'PD-05' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-06' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-07' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-08' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-09' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-10' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-11' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-12' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-13' => 'Sponsor, proposal sheet 2026-10-27, interim until deferred item 13',
        'PD-15' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-16' => 'Sponsor, proposal sheet 2026-10-27',
        'G2-01' => 'Sponsor, proposal sheet 2026-10-27',
        'G2-02' => 'Sponsor, proposal sheet 2026-10-27',
        'G3-01' => 'Sponsor, proposal sheet 2026-10-27',
        'G3-02' => 'Sponsor, proposal sheet 2026-10-27',
        'G3-03' => 'Sponsor, proposal sheet 2026-10-27',
        'G3-04' => 'Sponsor, proposal sheet 2026-10-27',
        'G3-05' => 'Sponsor, proposal sheet 2026-10-27',
        'G4-01' => 'Sponsor, proposal sheet 2026-10-27, interim until deferred item 6',
        'G4-02' => 'Sponsor, proposal sheet 2026-10-27',
        'G4-03' => 'Sponsor, proposal sheet 2026-10-27',
        'G4-04' => 'Sponsor, proposal sheet 2026-10-27',
        'G4-05' => 'Sponsor, proposal sheet 2026-10-27',
        'G4-06' => 'Sponsor, proposal sheet 2026-10-27, interim until deferred items 6 and 13',
        'G4-07' => 'Sponsor, proposal sheet 2026-10-27, interim until deferred item 10',
        'G5-01' => 'Sponsor, proposal sheet 2026-10-27',
        'G5-02' => 'Sponsor, proposal sheet 2026-10-27',
        'G5-03' => 'Sponsor, proposal sheet 2026-10-27, interim until deferred item 9',
        'G5-04' => 'Sponsor, proposal sheet 2026-10-27',
        'G5-05' => 'Sponsor, proposal sheet 2026-10-27',
        'PD-17' => 'Sponsor, 2026-10-27: changed to allow both CDN and local serving',
    ];

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

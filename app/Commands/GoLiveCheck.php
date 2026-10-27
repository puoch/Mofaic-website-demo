<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\GoLiveCheck as Check;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\GoLive;

/**
 * Lists everything that still blocks the live launch. Run it on the live
 * server before opening the site, and after any change to .env:
 *
 *   php spark golive:check
 *
 * Exits with status 1 while anything blocks the launch. It only reads.
 */
class GoLiveCheck extends BaseCommand
{
    protected $group       = 'MOFAIC';
    protected $name        = 'golive:check';
    protected $description = 'Lists what still blocks the live launch: settings, demo data, Ministry text, backups and open decisions.';
    protected $usage       = 'golive:check [--only-blockers]';
    protected $options     = ['--only-blockers' => 'Show only failed and warning lines.'];

    public function run(array $params): int
    {
        $results = (new Check(config(GoLive::class)))->run();
        $only    = CLI::getOption('only-blockers') !== null || array_key_exists('only-blockers', $params);
        $marks   = ['pass' => 'PASS', 'warn' => 'WARN', 'fail' => 'FAIL'];
        $colours = ['pass' => 'green', 'warn' => 'yellow', 'fail' => 'red'];
        $area    = '';

        foreach ($results as $r) {
            if ($only && $r['status'] === 'pass') {
                continue;
            }

            if ($r['area'] !== $area) {
                $area = $r['area'];
                CLI::newLine();
                CLI::write($area, 'white');
            }

            $line = '  ' . CLI::color($marks[$r['status']], $colours[$r['status']]) . '  ' . $r['check'];

            if ($r['status'] !== 'pass' && $r['detail'] !== '') {
                $line .= ' (' . $r['detail'] . ')';
            }

            CLI::write($line);
        }

        $count = array_count_values(array_column($results, 'status')) + ['pass' => 0, 'warn' => 0, 'fail' => 0];

        CLI::newLine();
        CLI::write("{$count['fail']} blocking, {$count['warn']} to review, {$count['pass']} passed.");

        if ($count['fail'] > 0) {
            CLI::write('Not ready for the live launch.', 'red');

            return EXIT_ERROR;
        }

        CLI::write('Nothing blocks the launch. A person still signs off the warnings.', 'green');

        return EXIT_SUCCESS;
    }
}

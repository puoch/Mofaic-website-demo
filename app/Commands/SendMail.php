<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Sends queued email. Run it every minute from cron on the server:
 *
 *   * * * * * cd /srv/mofaic-web/current && php spark mail:send
 */
class SendMail extends BaseCommand
{
    protected $group       = 'MOFAIC';
    protected $name        = 'mail:send';
    protected $description = 'Sends queued email through the configured SMTP relay.';
    protected $usage       = 'mail:send [--limit 50]';
    protected $options     = ['--limit' => 'Most messages to send in this run (default 50).'];

    public function run(array $params): void
    {
        $limit = max(1, (int) (CLI::getOption('limit') ?? 50));

        if ((string) config('Email')->fromEmail === '' || (string) config('Email')->SMTPHost === '') {
            CLI::error('Set email.fromEmail and email.SMTPHost in .env first.');

            return;
        }

        [$sent, $failed] = service('mailQueue')->send($limit);

        CLI::write("Sent {$sent}, failed {$failed}.");
    }
}

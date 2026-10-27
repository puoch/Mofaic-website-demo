<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Database\Seeds\DemoSeeder;
use Config\GoLive;
use Config\Submissions;
use Throwable;

/**
 * Lists what still stands between this installation and the live launch:
 * server settings, demonstration data, Ministry-supplied text, backups and
 * every proposal or deferred item in the decision log that MOFAIC has not
 * settled. Only reads; it changes nothing.
 *
 * 'fail' blocks the launch, 'warn' needs a person to look, 'pass' is fine.
 */
class GoLiveCheck
{
    /** @var list<array{area: string, check: string, status: string, detail: string}> */
    private array $results = [];

    public function __construct(private readonly GoLive $config, private readonly string $decisionLog = ROOTPATH . 'docs/decision-log.md')
    {
    }

    /**
     * @return list<array{area: string, check: string, status: string, detail: string}>
     */
    public function run(): array
    {
        $this->results = [];

        $this->server();
        $this->content();
        $this->operations();
        $this->decisions();

        return $this->results;
    }

    private function add(string $area, string $check, bool|string $status, string $detail = ''): void
    {
        if (is_bool($status)) {
            $status = $status ? 'pass' : 'fail';
        }

        $this->results[] = ['area' => $area, 'check' => $check, 'status' => $status, 'detail' => $detail];
    }

    private function server(): void
    {
        $app = config('App');
        $url = parse_url($app->baseURL) ?: [];
        $host = (string) ($url['host'] ?? '');

        $this->add('Server', 'CI_ENVIRONMENT is production', ENVIRONMENT === 'production', 'now ' . ENVIRONMENT);
        $this->add('Server', 'app.baseURL uses https and the real domain', ($url['scheme'] ?? '') === 'https' && ! in_array($host, ['localhost', '127.0.0.1', ''], true) && ! str_ends_with($host, '.invalid'), 'now ' . $app->baseURL . ' (domain is deferred item 12)');
        $this->add('Server', 'Every request is forced to https', $app->forceGlobalSecureRequests, 'set app.forceGlobalSecureRequests = true');
        $this->add('Server', 'Cookies are sent over https only', config('Cookie')->secure, 'set cookie.secure = true');
        $this->add('Server', 'Content Security Policy is on', $app->CSPEnabled);
        $assets = config('Assets');
        $this->add('Server', 'Bootstrap is served from this server', $assets->usesCdn() ? 'warn' : 'pass', $assets->usesCdn() ? 'assets.bootstrapSource = cdn, so visitors\' browsers contact ' . $assets->cdnOrigin() . '. Use local on the on-premises server (PD-17)' : 'assets.bootstrapSource = local');
        $this->add('Server', 'encryption.key is set (32 bytes)', strlen((string) config('Encryption')->key) === 32, 'generate with php spark key:generate');

        $db = db_connect();
        $this->add('Server', 'Database is MySQL', $db->DBDriver === 'MySQLi', 'now ' . $db->DBDriver);
        $this->add('Server', 'Database account is not root', ! in_array(strtolower((string) $db->username), ['root', ''], true));

        $email = config('Email');
        $this->add('Server', 'Outgoing mail relay is configured', $email->protocol === 'smtp' && $email->SMTPHost !== '' && $email->fromEmail !== '', 'set email.protocol, email.SMTPHost and email.fromEmail (the MOFAIC relay)');

        $this->add('Server', 'PHP does not show errors to visitors', ! filter_var(ini_get('display_errors'), FILTER_VALIDATE_BOOL), 'display_errors = Off in php.ini');
        $this->add('Server', 'PHP does not announce its version', ! filter_var(ini_get('expose_php'), FILTER_VALIDATE_BOOL) ? 'pass' : 'warn', 'expose_php = Off in the PHP-FPM php.ini (this reads the CLI php.ini, so confirm on the web server too)');
        $this->add('Server', 'writable/ is outside the web root', ! str_starts_with(realpath(WRITEPATH) ?: WRITEPATH, realpath(FCPATH) ?: FCPATH));
        $this->add('Server', 'Project files are not served by the web server', ...$this->projectFilesHidden($app->baseURL));

        $hosting = config('Hosting');
        $this->add('Server', 'Maintenance page is switched off', ! $hosting->maintenanceEnabled(), 'remove hosting.maintenanceToken from .env');
        $this->add('Server', 'Email is sent by cron, not during page requests', $hosting->sendMailInRequest ? 'warn' : 'pass', 'hosting.sendMailInRequest is for hosts without cron; run php spark mail:send from cron instead (PD-10)');
    }

    /**
     * Asks the site for composer.json, which sits next to .env. If it comes
     * back, the document root is the project folder rather than public/ and
     * .env with its passwords may be readable too (shared hosting, see
     * docs/shared-hosting.md).
     *
     * @return array{0: string, 1: string} status and detail
     */
    private function projectFilesHidden(string $baseURL): array
    {
        $url      = rtrim($baseURL, '/') . '/composer.json';
        $response = $this->fetch($url);

        if ($response === null) {
            return ['warn', "could not reach {$url} from this server; open it in a browser and expect 404"];
        }

        [$status, $body] = $response;

        // Some free hosts answer scripts with a browser check page instead.
        if ($status === 200 && str_contains($body, 'codeigniter4/framework')) {
            return ['fail', "{$url} is served: point the document root at public/ or add the root .htaccess"];
        }

        if ($status === 200) {
            return ['warn', "{$url} answered with another page (perhaps the host's browser check); open it in a browser and expect 404"];
        }

        return ['pass', "{$url} answers {$status}"];
    }

    /**
     * GET $url: the status and the start of the body, or null when it cannot
     * be reached.
     *
     * @return array{int, string}|null
     */
    protected function fetch(string $url): ?array
    {
        $context = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 5, 'ignore_errors' => true, 'follow_location' => 0]]);
        $body    = @file_get_contents($url, false, $context, 0, 65536);

        if ($body === false) {
            return null;
        }

        $headers = function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : ($http_response_header ?? []);

        return preg_match('#^HTTP/\S+\s+(\d{3})#', (string) ($headers[0] ?? ''), $m) === 1 ? [(int) $m[1], $body] : null;
    }

    private function content(): void
    {
        $db = db_connect();

        $demo = [];

        foreach (['departments', 'countries', 'missions'] as $table) {
            $demo[$table] = $db->table($table)->where('is_demo', 1)->countAllResults();
        }

        foreach (['pages' => 'title', 'articles' => 'title', 'galleries' => 'title', 'archive_items' => 'title', 'diaspora_country_pages' => 'title', 'site_blocks' => 'body'] as $table => $column) {
            $demo[$table] = $db->table($table)->like($column, DemoSeeder::LABEL)->countAllResults();
        }

        $demo['staff accounts'] = $db->table('auth_identities')->where('type', 'email_password')->like('secret', '@example.invalid', 'before')->countAllResults();
        $demo['notification rules'] = $db->table('notification_rules')->like('recipients', 'example.invalid')->countAllResults();

        $found = array_filter($demo);
        $this->add('Content', 'No demonstration data', $found === [], $found === [] ? '' : implode(', ', array_map(static fn ($t, $n) => "{$n} in {$t}", array_keys($found), $found)));

        $blocks = [];

        foreach (config(Submissions::class)->requiredBlocks as $keys) {
            $blocks = array_merge($blocks, $keys);
        }

        foreach (array_unique([...$blocks, 'contact_information', 'emergency_contacts']) as $key) {
            $body = (string) ($db->table('site_blocks')->select('body')->where('block_key', $key)->where('locale', 'en')->get()->getRow()?->body ?? '');
            $this->add('Content', "Ministry-approved text in \"{$key}\"", trim(strip_tags($body)) !== '' && ! str_contains($body, DemoSeeder::LABEL), 'enter the approved wording under Fixed text blocks');
        }

        $missions = $db->table('missions')->where('is_demo', 0)->countAllResults();
        $this->add('Content', 'Official mission records entered', $missions > 0, "{$missions} non-demo missions (authoritative mission data is deferred item 5)");

        $rules = $db->table('notification_rules')->where('is_active', 1)->notLike('recipients', 'example.invalid')->select('submission_type')->get()->getResultArray();
        $covered = array_unique(array_column($rules, 'submission_type'));

        foreach (array_keys(config(Submissions::class)->types) as $type) {
            $this->add('Content', "Someone is notified of new {$type} submissions", in_array($type, $covered, true) ? 'pass' : 'warn', 'add a notification rule, or staff must check the inbox themselves');
        }
    }

    private function operations(): void
    {
        $db = db_connect();

        $superAdmins = $db->table('auth_groups_users g')
            ->join('users u', 'u.id = g.user_id')
            ->where('g.group', 'superadmin')
            // Shield blocks sign-in with status 'banned'; 'active' is only email activation.
            ->groupStart()->where('u.status', null)->orWhere('u.status !=', 'banned')->groupEnd()
            ->where('u.deleted_at', null)
            ->countAllResults();
        $this->add('Operations', "At least {$this->config->minimumSuperAdmins} active Super Administrators", $superAdmins >= $this->config->minimumSuperAdmins ? 'pass' : 'warn', "{$superAdmins} now");

        $latest = 0;

        foreach (glob(rtrim($this->config->backupDirectory, '/') . '/*/MANIFEST') ?: [] as $manifest) {
            $latest = max($latest, (int) filemtime($manifest));
        }

        $age = $latest === 0 ? null : (int) floor((time() - $latest) / 3600);
        $this->add('Operations', "A complete backup from the last {$this->config->maxBackupAgeHours} hours", $age !== null && $age <= $this->config->maxBackupAgeHours, $age === null ? "none found in {$this->config->backupDirectory}" : "latest is {$age} hours old");

        try {
            $stuck = $db->table('mail_queue')->where('status', 'queued')->where('created_at <', date('Y-m-d H:i:s', time() - 3600))->countAllResults();
            $failed = $db->table('mail_queue')->where('status', 'failed')->countAllResults();
        } catch (Throwable) {
            $stuck = $failed = 0;
        }

        $this->add('Operations', 'Mail queue is being sent (cron runs mail:send)', $stuck === 0 ? 'pass' : 'warn', "{$stuck} messages waiting over an hour");
        $this->add('Operations', 'No failed outgoing mail', $failed === 0 ? 'pass' : 'warn', "{$failed} failed");
    }

    private function decisions(): void
    {
        $log = is_file($this->decisionLog) ? (string) file_get_contents($this->decisionLog) : '';

        if ($log === '') {
            $this->add('Decisions', 'Decision log found', false, $this->decisionLog);

            return;
        }

        foreach ($this->proposalsInUse($log) as $id => $text) {
            $source = $this->config->approvedProposals[$id] ?? null;
            $this->add('Decisions', "{$id} approved by MOFAIC", $source !== null, $source ?? $text);
        }

        foreach ($this->deferredItems($log) as $number => $text) {
            $source = $this->config->resolvedDeferredItems[$number] ?? null;
            $this->add('Decisions', "Deferred item {$number} settled", $source !== null, $source ?? $text);
        }
    }

    /**
     * @return array<string, string> ID => proposal text, from the "Proposed, in use" table
     */
    public function proposalsInUse(string $log): array
    {
        $section = $this->section($log, 'Proposed, in use');
        preg_match_all('/^\|\s*([A-Z0-9]+-\d+)\s*\|\s*([^|]+)\|/m', $section, $m, PREG_SET_ORDER);

        $items = [];

        foreach ($m as $row) {
            $items[$row[1]] = $this->short($row[2]);
        }

        return $items;
    }

    /**
     * @return array<int, string> number => text, from the "Deferred" list
     */
    public function deferredItems(string $log): array
    {
        preg_match_all('/^(\d+)\.\s+(.+)$/m', $this->section($log, 'Deferred'), $m, PREG_SET_ORDER);

        $items = [];

        foreach ($m as $row) {
            $items[(int) $row[1]] = $this->short($row[2]);
        }

        return $items;
    }

    private function section(string $log, string $heading): string
    {
        $parts = preg_split('/^## /m', $log) ?: [];

        foreach ($parts as $part) {
            if (str_starts_with($part, $heading)) {
                return $part;
            }
        }

        return '';
    }

    private function short(string $text): string
    {
        $text = trim(str_replace('`', '', $text));

        return mb_strlen($text) > 90 ? mb_substr($text, 0, 87) . '...' : $text;
    }
}

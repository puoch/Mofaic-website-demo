<?php

namespace Config;

use CodeIgniter\Events\Events;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Exceptions\FrameworkException;
use CodeIgniter\HotReloader\HotReloader;

/*
 * --------------------------------------------------------------------
 * Application Events
 * --------------------------------------------------------------------
 * Events allow you to tap into the execution of the program without
 * modifying or extending core files. This file provides a central
 * location to define your events, though they can always be added
 * at run-time, also, if needed.
 *
 * You create code that can execute by subscribing to events with
 * the 'on()' method. This accepts any form of callable, including
 * Closures, that will be executed when the event is triggered.
 *
 * Example:
 *      Events::on('create', [$myInstance, 'myMethod']);
 */

Events::on('pre_system', static function (): void {
    if (ENVIRONMENT !== 'testing') {
        $value = ini_get('zlib.output_compression');

        if (filter_var($value, FILTER_VALIDATE_BOOLEAN) || (int) $value > 0) {
            throw FrameworkException::forEnabledZlibOutputCompression();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        ob_start(static fn ($buffer) => $buffer);
    }

    /*
     * --------------------------------------------------------------------
     * Debug Toolbar Listeners.
     * --------------------------------------------------------------------
     * If you delete, they will no longer be collected.
     */
    if (CI_DEBUG && ! is_cli()) {
        Events::on('DBQuery', 'CodeIgniter\Debug\Toolbar\Collectors\Database::collect');
        service('toolbar')->respond();
        // Hot Reload route - for framework use on the hot reloader.
        if (ENVIRONMENT === 'development') {
            service('routes')->get('__hot-reload', static function (): void {
                (new HotReloader())->run();
            });
        }
    }
});

/*
 * --------------------------------------------------------------------
 * Staff sign-in audit events (brief section 4: audit administration activity)
 * --------------------------------------------------------------------
 * The failed-login listener records only the email address that was tried.
 * Shield passes the password in $credentials; it must never be stored.
 */
Events::on('login', static function (User $user): void {
    service('auditLog')->record('auth.login', (int) $user->id, 'user', $user->id);
});

Events::on('logout', static function (?User $user = null): void {
    service('auditLog')->record('auth.logout', $user === null ? null : (int) $user->id, 'user', $user?->id);
});

Events::on('failedLogin', static function (array $credentials): void {
    $email = isset($credentials['email']) && is_string($credentials['email']) ? $credentials['email'] : null;
    service('auditLog')->record('auth.login_failed', null, null, null, ['email' => $email]);
});

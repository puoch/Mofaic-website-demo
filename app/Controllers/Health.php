<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Health check for monitoring. Reveals only whether the application and its
 * database respond; no versions, paths or error messages.
 */
class Health extends BaseController
{
    public function index(): ResponseInterface
    {
        try {
            db_connect()->query('SELECT 1');
            $database = 'ok';
        } catch (Throwable) {
            $database = 'unavailable';
        }

        $healthy = $database === 'ok';

        return $this->response
            ->setStatusCode($healthy ? 200 : 503)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(['status' => $healthy ? 'ok' : 'degraded', 'database' => $database]);
    }
}

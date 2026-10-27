<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * On hosting without cron (Config\Hosting::$sendMailInRequest), each staff
 * page sends a few waiting messages: retries and newsletter issues. Does
 * nothing otherwise. A mail failure never breaks the page.
 */
class SendQueuedMail implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $hosting = config('Hosting');

        if ($hosting->sendMailInRequest) {
            try {
                service('mailQueue')->send($hosting->mailPerRequest);
            } catch (Throwable $e) {
                log_message('error', 'Sending queued mail failed: {message}', ['message' => $e->getMessage()]);
            }
        }

        return null;
    }
}

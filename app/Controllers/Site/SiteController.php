<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Shared helpers for public website controllers.
 */
abstract class SiteController extends BaseController
{
    protected function locale(): string
    {
        return $this->request->getLocale();
    }

    protected function notFound(): never
    {
        throw PageNotFoundException::forPageNotFound();
    }

    /**
     * @return array<string, mixed>
     */
    protected function requireRow(?array $row): array
    {
        if ($row === null) {
            $this->notFound();
        }

        return $row;
    }

    /**
     * Published rows of a content table in the current locale.
     */
    protected function published(string $table): \CodeIgniter\Database\BaseBuilder
    {
        return db_connect()->table($table)->where(['status' => 'published', 'locale' => $this->locale()]);
    }
}

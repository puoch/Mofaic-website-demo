<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Audit extends BaseController
{
    public function index(): string
    {
        return view('admin/audit', ['events' => service('auditLog')->latest(100)]);
    }
}

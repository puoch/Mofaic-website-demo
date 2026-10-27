<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\ContentTypes;

/**
 * Publishing dashboard: what exists, what is still in draft and what changed
 * recently, across every content type with a publishing status.
 */
class Publishing extends BaseController
{
    public function index(): string
    {
        $types  = config(ContentTypes::class);
        $db     = db_connect();
        $user   = auth()->user();
        $policy = service('contentPolicy');

        $summary = [];
        $recent  = [];

        foreach ($types->types as $key => $definition) {
            if (empty($definition['status'])) {
                continue;
            }

            $counts = ['draft' => 0, 'published' => 0];
            foreach ($db->table($definition['table'])->select('status, COUNT(*) AS n')->groupBy('status')->get()->getResultArray() as $row) {
                $counts[$row['status']] = (int) $row['n'];
            }

            $summary[$key] = ['label' => $definition['label'], 'counts' => $counts, 'canCreate' => $policy->canCreate($user, $key)];

            foreach ($db->table($definition['table'])->orderBy('updated_at', 'DESC')->limit(10)->get()->getResultArray() as $row) {
                $recent[] = [
                    'type'       => $key,
                    'typeLabel'  => $definition['singular'],
                    'id'         => (int) $row['id'],
                    'title'      => $row[$definition['titleField']],
                    'status'     => $row['status'],
                    'updated_at' => $row['updated_at'],
                ];
            }
        }

        usort($recent, static fn (array $a, array $b): int => strcmp((string) $b['updated_at'], (string) $a['updated_at']));

        $log = $db->table('audit_events')
            ->like('event', 'content.', 'after')
            ->orderBy('id', 'DESC')
            ->limit(15)
            ->get()
            ->getResultArray();

        return view('admin/publishing', [
            'summary'  => $summary,
            'recent'   => array_slice($recent, 0, 15),
            'log'      => $user->can('audit.view') ? $log : [],
            'canAudit' => $user->can('audit.view'),
        ]);
    }
}

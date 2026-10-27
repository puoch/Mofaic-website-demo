<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;
use Config\Submissions;

/**
 * The Public Inquiries Inbox (/admin/inquiries) and the Diaspora inbox
 * (/admin/diaspora). They are separate inboxes with separate permissions:
 * inquiries.* for public inquiries, and the per-person diaspora.* grants for
 * registrations and contributions (brief sections 2 and 3).
 *
 * Opening a submission is written to the audit log. Notes are staff-only.
 */
class Inbox extends BaseController
{
    private const PER_PAGE = 25;

    private Submissions $config;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, ResponseInterface $response, \Psr\Log\LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);
        $this->config = config(Submissions::class);
    }

    public function index(string $inbox): ResponseInterface|string
    {
        $types = $this->typesIn($inbox);

        if ($types === []) {
            return $this->forbidden();
        }

        $type = (string) $this->request->getGet('type');
        $type = isset($types[$type]) ? $type : array_key_first($types);

        $filters = [];
        $status  = (string) $this->request->getGet('status');
        if (isset($this->config->statuses[$status])) {
            $filters['status'] = $status;
        }
        if ($this->request->getGet('mine') === '1') {
            $filters['assigned_to'] = (int) $this->user()->id;
        }

        $page   = max(1, (int) $this->request->getGet('page'));
        $result = service('submissions')->list($type, $filters, $page, self::PER_PAGE);

        return view('admin/inbox/index', [
            'inbox'     => $inbox,
            'types'     => $types,
            'type'      => $type,
            'status'    => $filters['status'] ?? '',
            'mine'      => isset($filters['assigned_to']),
            'rows'      => $result['rows'],
            'page'      => $page,
            'pages'     => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'statuses'  => $this->config->statuses,
            'staff'     => service('staffDirectory')->all(),
            'countries' => array_column(db_connect()->table('countries')->get()->getResultArray(), 'name', 'id'),
            'categories'=> $this->config->contributionCategories,
        ]);
    }

    public function show(string $inbox, string $type, int $id): ResponseInterface|string
    {
        $definition = $this->definition($inbox, $type);

        if (! $this->user()->can($definition['view'])) {
            return $this->forbidden();
        }

        $row = $this->row($type, $id);
        service('submissions')->recordView($type, $id, (int) $this->user()->id);

        $db = db_connect();

        return view('admin/inbox/show', [
            'inbox'      => $inbox,
            'type'       => $type,
            'definition' => $definition,
            'row'        => $row,
            'canManage'  => $this->user()->can($definition['manage']),
            'statuses'   => $this->config->statuses,
            'assignees'  => service('staffDirectory')->withPermission($definition['view']),
            'staff'      => service('staffDirectory')->all(),
            'notes'      => service('submissions')->notes($type, $id),
            'country'    => isset($row['country_id']) ? $db->table('countries')->where('id', $row['country_id'])->get()->getRowArray() : null,
            'mission'    => ! empty($row['chosen_mission_id']) ? $db->table('missions')->where('id', $row['chosen_mission_id'])->get()->getRowArray() : null,
            'categories' => $this->config->contributionCategories,
        ]);
    }

    public function status(string $inbox, string $type, int $id): ResponseInterface|RedirectResponse
    {
        if (($denied = $this->requireManage($inbox, $type, $id)) !== null) {
            return $denied;
        }

        $status = (string) $this->request->getPost('status');

        if (! isset($this->config->statuses[$status])) {
            return $this->back($inbox, $type, $id)->with('error', 'Choose one of the listed statuses.');
        }

        service('submissions')->changeStatus($type, $id, $status, (int) $this->user()->id);

        return $this->back($inbox, $type, $id)->with('message', 'Status changed to ' . $this->config->statuses[$status] . '.');
    }

    public function assign(string $inbox, string $type, int $id): ResponseInterface|RedirectResponse
    {
        if (($denied = $this->requireManage($inbox, $type, $id)) !== null) {
            return $denied;
        }

        $value      = (string) $this->request->getPost('assigned_to');
        $assignees  = service('staffDirectory')->withPermission($this->definition($inbox, $type)['view']);
        $assigneeId = $value === '' ? null : (int) $value;

        // Only staff who may open this inbox can be made responsible.
        if ($assigneeId !== null && ! isset($assignees[$assigneeId])) {
            return $this->back($inbox, $type, $id)->with('error', 'Choose one of the listed staff members.');
        }

        service('submissions')->assign($type, $id, $assigneeId, (int) $this->user()->id);

        return $this->back($inbox, $type, $id)->with('message', $assigneeId === null ? 'Assignment removed.' : 'Assigned to ' . $assignees[$assigneeId] . '.');
    }

    public function note(string $inbox, string $type, int $id): ResponseInterface|RedirectResponse
    {
        if (($denied = $this->requireManage($inbox, $type, $id)) !== null) {
            return $denied;
        }

        $note = trim((string) $this->request->getPost('note'));

        if ($note === '' || mb_strlen($note) > 5000) {
            return $this->back($inbox, $type, $id)->with('error', 'Write a note of up to 5,000 characters.');
        }

        service('submissions')->addNote($type, $id, $note, (int) $this->user()->id);

        return $this->back($inbox, $type, $id)->with('message', 'Note added.');
    }

    /**
     * Submission types of an inbox that the user may view.
     *
     * @return array<string, array<string, mixed>>
     */
    private function typesIn(string $inbox): array
    {
        return array_filter(
            $this->config->types,
            fn (array $t): bool => $t['inbox'] === $inbox && $this->user()->can($t['view']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function definition(string $inbox, string $type): array
    {
        $definition = $this->config->types[$type] ?? null;

        if ($definition === null || $definition['inbox'] !== $inbox) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $definition;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $type, int $id): array
    {
        return service('submissions')->find($type, $id) ?? throw PageNotFoundException::forPageNotFound();
    }

    private function requireManage(string $inbox, string $type, int $id): ?ResponseInterface
    {
        if (! $this->user()->can($this->definition($inbox, $type)['manage'])) {
            return $this->forbidden();
        }

        $this->row($type, $id);

        return null;
    }

    private function back(string $inbox, string $type, int $id): RedirectResponse
    {
        return redirect()->to(site_url("admin/{$inbox}/{$type}/{$id}"));
    }

    private function user(): User
    {
        return auth()->user();
    }

    private function forbidden(): ResponseInterface
    {
        return $this->response->setStatusCode(403)->setBody(view('admin/forbidden'));
    }
}

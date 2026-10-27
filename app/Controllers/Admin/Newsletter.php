<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;

/**
 * Sends a published diaspora newsletter to confirmed subscribers. Whoever
 * may edit the article may send it, which means editors of a diaspora
 * department (brief sections 2 and 3). Subscriber addresses are not shown
 * here: they are personal data.
 */
class Newsletter extends BaseController
{
    public function index(): string
    {
        $issues = db_connect()->table('articles')
            ->select('articles.*, newsletter_sends.queued_count, newsletter_sends.created_at AS sent_at')
            ->join('newsletter_sends', 'newsletter_sends.article_id = articles.id', 'left')
            ->where('articles.category', 'diaspora_newsletter')
            ->orderBy('articles.updated_at', 'DESC')
            ->get()
            ->getResultArray();

        $policy = service('contentPolicy');

        foreach ($issues as &$issue) {
            $issue['can_send'] = $issue['status'] === 'published' && $issue['sent_at'] === null && $policy->canEdit(auth()->user(), 'articles', $issue);
        }

        return view('admin/newsletter', ['issues' => $issues, 'counts' => service('newsletter')->counts()]);
    }

    public function send(int $articleId): ResponseInterface|RedirectResponse
    {
        $article = db_connect()->table('articles')->where('id', $articleId)->get()->getRowArray();

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! service('contentPolicy')->canEdit(auth()->user(), 'articles', $article)) {
            return $this->response->setStatusCode(403)->setBody(view('admin/forbidden'));
        }

        try {
            $count = service('newsletter')->sendIssue($article, (int) auth()->id());
        } catch (InvalidArgumentException $e) {
            return redirect()->to(site_url('admin/newsletter'))->with('error', $e->getMessage());
        }

        return redirect()->to(site_url('admin/newsletter'))->with('message', "Queued for {$count} confirmed subscribers.");
    }
}

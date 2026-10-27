<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Public forms and the two staff inboxes (CONTACTS and SSD DIASPORA).
 *
 * Values marked PD-xx are proposed defaults from the decision log, not
 * Ministry policy. Change them here once MOFAIC decides.
 */
class Submissions extends BaseConfig
{
    /**
     * Submission types. Public inquiries and diaspora submissions are kept in
     * separate inboxes with separate permissions (brief sections 2 and 3).
     *
     * @var array<string, array{table: string, label: string, inbox: string, view: string, manage: string, prefix: string}>
     */
    public array $types = [
        'inquiry' => [
            'table'  => 'inquiries',
            'label'  => 'Public inquiry',
            'inbox'  => 'inquiries',
            'view'   => 'inquiries.view',
            'manage' => 'inquiries.manage',
            'prefix' => 'INQ',
        ],
        'registration' => [
            'table'  => 'diaspora_registrations',
            'label'  => 'Diaspora registration',
            'inbox'  => 'diaspora',
            'view'   => 'diaspora.submissions-view',
            'manage' => 'diaspora.submissions-manage',
            'prefix' => 'REG',
        ],
        'contribution' => [
            'table'  => 'diaspora_contributions',
            'label'  => 'Diaspora contribution',
            'inbox'  => 'diaspora',
            'view'   => 'diaspora.submissions-view',
            'manage' => 'diaspora.submissions-manage',
            'prefix' => 'CON',
        ],
    ];

    /**
     * PD-05: inbox status names (proposed, brief section 3).
     *
     * @var array<string, string>
     */
    public array $statuses = [
        'new'                  => 'New',
        'in_progress'          => 'In progress',
        'awaiting_information' => 'Awaiting information',
        'closed'               => 'Closed',
    ];

    /**
     * Contribution categories (confirmed, brief section 2).
     *
     * @var array<string, string>
     */
    public array $contributionCategories = [
        'ideas'       => 'Ideas and recommendations',
        'feedback'    => 'Feedback',
        'development' => 'Development initiatives and proposals',
        'investment'  => 'Donation and investment information',
    ];

    /**
     * PD-08: the name is required on diaspora registration (proposed; brief
     * section 6 item 6 leaves this open). Nature of inquiry stays optional.
     */
    public bool $registrationNameRequired = true;

    /**
     * Proposed public inquiry fields (brief section 6 item 8 leaves them open):
     * name, email and message required; telephone and subject optional.
     *
     * @var array<string, bool> field => required
     */
    public array $inquiryFields = [
        'name'    => true,
        'email'   => true,
        'phone'   => false,
        'subject' => false,
        'message' => true,
    ];

    /**
     * Text blocks that must hold MOFAIC-approved text before a form opens.
     * While any is empty the form is not shown (brief section 6 item 6).
     *
     * @var array<string, list<string>>
     */
    public array $requiredBlocks = [
        'inquiry'      => ['privacy_notice'],
        'registration' => ['privacy_notice', 'diaspora_registration_statement'],
        'contribution' => ['privacy_notice'],
        'newsletter'   => ['privacy_notice'],
    ];

    /**
     * PD-11: spam protection without outside services.
     */
    public string $honeypotField = 'website_url';

    /** Fewer seconds than this between showing and sending a form is treated as automated. */
    public int $minimumFillSeconds = 3;

    /** A form older than this must be reloaded. */
    public int $maximumFormAgeSeconds = 7200;

    /** Submissions per form, per visitor address, per hour. */
    public int $submissionsPerHour = 10;
}

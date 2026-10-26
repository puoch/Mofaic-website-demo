<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

/**
 * The four staff roles confirmed in the requirements brief (section 3).
 *
 * Group-to-permission assignments below are the Gate 2 starting point and are
 * still subject to the final permissions review (brief section 3, GOV-08).
 * See docs/permissions.md.
 *
 * Diaspora submission access is deliberately not granted to any group,
 * including the Super Administrator (decision PD-02). It is given to named
 * staff accounts one by one with `$user->addPermission(...)`.
 */
class AuthGroups extends ShieldAuthGroups
{
    /**
     * Staff accounts are created by a Super Administrator, never by public
     * registration, so new accounts start with the least-privileged role.
     */
    public string $defaultGroup = 'readonly';

    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Administrator',
            'description' => 'Manages staff accounts, roles and system settings.',
        ],
        'ministryadmin' => [
            'title'       => 'Ministry Administrator',
            'description' => 'Central Ministry administrator. Publishes across all departments and mission activities.',
        ],
        'editor' => [
            'title'       => 'Department Editor',
            'description' => 'Publishes directly within the departments they belong to.',
        ],
        'readonly' => [
            'title'       => 'Read-Only Staff',
            'description' => 'Can view the administration area without making changes.',
        ],
    ];

    public array $permissions = [
        'admin.access'                 => 'Can open the administration area',
        'users.manage'                 => 'Can create, edit and deactivate staff accounts and assign roles',
        'settings.manage'              => 'Can change system settings',
        'audit.view'                   => 'Can view the audit log',
        'content.view'                 => 'Can view all content, including drafts',
        'content.publish-department'   => 'Can publish content in departments the user belongs to',
        'content.publish-any'          => 'Can publish content in any department',
        'content.restore'              => 'Can restore a retained earlier version of content',
        'missions.manage'              => 'Can manage mission records',
        'missions.publish-activities'  => 'Can publish activities of diplomatic missions',
        'inquiries.view'               => 'Can view the Public Inquiries Inbox',
        'inquiries.manage'             => 'Can assign, annotate and change status of public inquiries',
        'diaspora.submissions-view'    => 'Can view the Diaspora Submissions inbox (granted per person only)',
        'diaspora.submissions-manage'  => 'Can assign, annotate and change status of diaspora submissions (granted per person only)',
    ];

    public array $matrix = [
        'superadmin' => [
            'admin.access',
            'users.manage',
            'settings.manage',
            'audit.view',
            'content.view',
        ],
        'ministryadmin' => [
            'admin.access',
            'audit.view',
            'content.view',
            'content.publish-any',
            'content.restore',
            'missions.manage',
            'missions.publish-activities',
            'inquiries.view',
            'inquiries.manage',
        ],
        'editor' => [
            'admin.access',
            'content.view',
            'content.publish-department',
        ],
        'readonly' => [
            'admin.access',
            'content.view',
        ],
    ];
}

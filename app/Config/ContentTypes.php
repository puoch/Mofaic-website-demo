<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Content types managed in the administration area.
 *
 * Each type names its table, its form fields and who may change it:
 *  - scope 'department': Department Editors in the record's department, or
 *    anyone with content.publish-any. Mission activities additionally need
 *    missions.publish-activities (brief sections 2 and 3).
 *  - scope 'missions':   missions.manage (central Ministry administrators).
 *  - scope 'site':       content.publish-any (central Ministry administrators).
 *
 * Types with 'status' are published and unpublished; types with 'revisions'
 * keep every saved version and can be restored (content.restore).
 *
 * Field types: text, slug, textarea, markdown, links, select, department,
 * checkbox, number, url, email, tags.
 */
class ContentTypes extends BaseConfig
{
    /**
     * @var array<string, array<string, mixed>>
     */
    public array $types = [
        'articles' => [
            'table'     => 'articles',
            'label'     => 'News and statements',
            'singular'  => 'Article',
            'titleField'=> 'title',
            'scope'     => 'department',
            'status'    => true,
            'revisions' => true,
            'media'     => true,
            'localized' => true,
            'slugFrom'  => 'title',
            'list'      => ['title', 'category', 'status', 'published_at'],
            'fields'    => [
                'category'      => ['label' => 'Category', 'type' => 'select', 'options' => 'articleCategories', 'rules' => 'required|in_list[news,statement,mission_activity]'],
                'title'         => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|max_length[255]'],
                'slug'          => ['label' => 'Web address', 'type' => 'slug', 'rules' => 'permit_empty|max_length[190]|alpha_dash', 'help' => 'Leave empty to create it from the title.'],
                'summary'       => ['label' => 'Summary', 'type' => 'textarea', 'rules' => 'permit_empty|max_length[1000]'],
                'body'          => ['label' => 'Text', 'type' => 'markdown', 'rules' => 'permit_empty|max_length[60000]'],
                'department_id' => ['label' => 'Department', 'type' => 'department', 'rules' => 'permit_empty|is_natural_no_zero'],
                'mission_id'    => ['label' => 'Mission (mission activities only)', 'type' => 'select', 'options' => 'missions', 'rules' => 'permit_empty|is_natural_no_zero'],
                'is_featured'   => ['label' => 'Feature on the home page', 'type' => 'checkbox'],
                'tags'          => ['label' => 'Tags', 'type' => 'tags', 'help' => 'Separate tags with commas. Tag "Minister\'s engagements" to show an article in that home page area.'],
            ],
        ],
        'pages' => [
            'table'     => 'pages',
            'label'     => 'Pages',
            'singular'  => 'Page',
            'titleField'=> 'title',
            'scope'     => 'department',
            'status'    => true,
            'revisions' => true,
            'media'     => true,
            'localized' => true,
            'slugFrom'  => 'title',
            'list'      => ['title', 'section', 'path', 'status'],
            'fields'    => [
                'section'        => ['label' => 'Section', 'type' => 'select', 'options' => 'pageSections', 'rules' => 'required|in_list[about-mfaic,about-south-sudan,services]', 'lockedForSystem' => true],
                'parent_id'      => ['label' => 'Parent page', 'type' => 'select', 'options' => 'pages', 'rules' => 'permit_empty|is_natural_no_zero', 'lockedForSystem' => true],
                'title'          => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|max_length[255]'],
                'slug'           => ['label' => 'Web address', 'type' => 'slug', 'rules' => 'permit_empty|max_length[190]|alpha_dash', 'lockedForSystem' => true, 'help' => 'Leave empty to create it from the title.'],
                'summary'        => ['label' => 'Summary', 'type' => 'textarea', 'rules' => 'permit_empty|max_length[1000]'],
                'body'           => ['label' => 'Text', 'type' => 'markdown', 'rules' => 'permit_empty|max_length[60000]'],
                'external_links' => ['label' => 'Official external links', 'type' => 'links', 'rules' => 'permit_empty|max_length[5000]', 'help' => 'One link per line: Label | https://address'],
                'department_id'  => ['label' => 'Department', 'type' => 'department', 'rules' => 'permit_empty|is_natural_no_zero'],
                'sort_order'     => ['label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|integer'],
            ],
        ],
        'galleries' => [
            'table'     => 'galleries',
            'label'     => 'Gallery albums',
            'singular'  => 'Album',
            'titleField'=> 'title',
            'scope'     => 'department',
            'status'    => true,
            'revisions' => true,
            'media'     => true,
            'localized' => true,
            'slugFrom'  => 'title',
            'list'      => ['title', 'status', 'published_at'],
            'fields'    => [
                'title'         => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|max_length[255]'],
                'slug'          => ['label' => 'Web address', 'type' => 'slug', 'rules' => 'permit_empty|max_length[190]|alpha_dash'],
                'summary'       => ['label' => 'Description', 'type' => 'textarea', 'rules' => 'permit_empty|max_length[2000]'],
                'department_id' => ['label' => 'Department', 'type' => 'department', 'rules' => 'permit_empty|is_natural_no_zero'],
            ],
        ],
        'archives' => [
            'table'     => 'archive_items',
            'label'     => 'Archives',
            'singular'  => 'Archive item',
            'titleField'=> 'title',
            'scope'     => 'department',
            'status'    => true,
            'revisions' => true,
            'media'     => true,
            'localized' => true,
            'slugFrom'  => 'title',
            'list'      => ['title', 'status', 'published_at'],
            'fields'    => [
                'title'              => ['label' => 'Title', 'type' => 'text', 'rules' => 'required|max_length[255]'],
                'slug'               => ['label' => 'Web address', 'type' => 'slug', 'rules' => 'permit_empty|max_length[190]|alpha_dash'],
                'body'               => ['label' => 'Text', 'type' => 'markdown', 'rules' => 'permit_empty|max_length[60000]'],
                'department_id'      => ['label' => 'Department', 'type' => 'department', 'rules' => 'permit_empty|is_natural_no_zero'],
                'related_article_id' => ['label' => 'Related news or statement', 'type' => 'select', 'options' => 'articles', 'rules' => 'permit_empty|is_natural_no_zero'],
                'related_gallery_id' => ['label' => 'Related gallery album', 'type' => 'select', 'options' => 'galleries', 'rules' => 'permit_empty|is_natural_no_zero'],
            ],
        ],
        'missions' => [
            'table'     => 'missions',
            'label'     => 'Missions',
            'singular'  => 'Mission',
            'titleField'=> 'name',
            'scope'     => 'missions',
            'status'    => true,
            'revisions' => true,
            'media'     => true,
            'localized' => true,
            'slugFrom'  => 'name',
            'list'      => ['name', 'category', 'city', 'status'],
            'fields'    => [
                'name'                  => ['label' => 'Mission name', 'type' => 'text', 'rules' => 'required|max_length[255]'],
                'slug'                  => ['label' => 'Web address', 'type' => 'slug', 'rules' => 'permit_empty|max_length[190]|alpha_dash'],
                'category'              => ['label' => 'Type', 'type' => 'select', 'options' => 'missionCategories', 'rules' => 'required|in_list[abroad_embassy,abroad_consulate,abroad_permanent_mission,abroad_non_resident_mission,foreign_embassy,foreign_international_agency]'],
                'location_country_id'   => ['label' => 'Located in (country)', 'type' => 'select', 'options' => 'countries', 'rules' => 'permit_empty|is_natural_no_zero'],
                'city'                  => ['label' => 'City', 'type' => 'text', 'rules' => 'permit_empty|max_length[190]'],
                'address'               => ['label' => 'Physical address', 'type' => 'textarea', 'rules' => 'permit_empty|max_length[1000]'],
                'represents_country_id' => ['label' => 'Country represented (foreign embassies in South Sudan)', 'type' => 'select', 'options' => 'countries', 'rules' => 'permit_empty|is_natural_no_zero'],
                'phone'                 => ['label' => 'Telephone', 'type' => 'text', 'rules' => 'permit_empty|max_length[100]'],
                'email'                 => ['label' => 'Email', 'type' => 'email', 'rules' => 'permit_empty|valid_email|max_length[190]'],
                'website'               => ['label' => 'Website (optional)', 'type' => 'url', 'rules' => 'permit_empty|valid_url_strict[https,http]|max_length[255]'],
                'head_of_mission_title' => ['label' => 'Head of mission: title', 'type' => 'text', 'rules' => 'permit_empty|max_length[190]'],
                'head_of_mission_name'  => ['label' => 'Head of mission: name', 'type' => 'text', 'rules' => 'permit_empty|max_length[190]'],
                'body'                  => ['label' => 'About the mission', 'type' => 'markdown', 'rules' => 'permit_empty|max_length[30000]'],
            ],
        ],
        'countries' => [
            'table'     => 'countries',
            'label'     => 'Countries',
            'singular'  => 'Country',
            'titleField'=> 'name',
            'scope'     => 'missions',
            'list'      => ['name', 'iso_code'],
            'fields'    => [
                'name'     => ['label' => 'Country name', 'type' => 'text', 'rules' => 'required|max_length[190]'],
                'iso_code' => ['label' => 'Two-letter ISO 3166 code', 'type' => 'text', 'rules' => 'required|exact_length[2]|alpha'],
            ],
        ],
        'tags' => [
            'table'     => 'tags',
            'label'     => 'Tags',
            'singular'  => 'Tag',
            'titleField'=> 'name',
            'scope'     => 'site',
            'slugFrom'  => 'name',
            'list'      => ['name', 'slug'],
            'fields'    => [
                'name' => ['label' => 'Tag', 'type' => 'text', 'rules' => 'required|max_length[190]'],
                'slug' => ['label' => 'Web address', 'type' => 'slug', 'rules' => 'permit_empty|max_length[120]|alpha_dash', 'lockedForSystem' => true],
            ],
        ],
        'quick_links' => [
            'table'     => 'quick_links',
            'label'     => 'Quick links',
            'singular'  => 'Quick link',
            'titleField'=> 'label',
            'scope'     => 'site',
            'localized' => true,
            'list'      => ['label', 'url', 'sort_order', 'is_active'],
            'fields'    => [
                'label'      => ['label' => 'Label', 'type' => 'text', 'rules' => 'required|max_length[190]'],
                'url'        => ['label' => 'Address', 'type' => 'url', 'rules' => 'required|max_length[500]|valid_url_strict[https,http]'],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|integer'],
                'is_active'  => ['label' => 'Show on the home page', 'type' => 'checkbox'],
            ],
        ],
        'social_accounts' => [
            'table'     => 'social_accounts',
            'label'     => 'Social media accounts',
            'singular'  => 'Social media account',
            'titleField'=> 'label',
            'scope'     => 'site',
            'list'      => ['label', 'platform', 'url', 'is_active'],
            'fields'    => [
                'platform'   => ['label' => 'Platform', 'type' => 'text', 'rules' => 'required|max_length[60]'],
                'label'      => ['label' => 'Account name', 'type' => 'text', 'rules' => 'required|max_length[190]'],
                'url'        => ['label' => 'Address', 'type' => 'url', 'rules' => 'required|max_length[500]|valid_url_strict[https]'],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|integer'],
                'is_active'  => ['label' => 'Show on the website', 'type' => 'checkbox'],
            ],
        ],
        'org_units' => [
            'table'     => 'org_units',
            'label'     => 'Organogram',
            'singular'  => 'Organisational unit',
            'titleField'=> 'name',
            'scope'     => 'site',
            'localized' => true,
            'list'      => ['name', 'head_title', 'sort_order'],
            'fields'    => [
                'name'       => ['label' => 'Unit name', 'type' => 'text', 'rules' => 'required|max_length[255]'],
                'head_title' => ['label' => 'Title of the head of unit', 'type' => 'text', 'rules' => 'permit_empty|max_length[190]'],
                'parent_id'  => ['label' => 'Reports to', 'type' => 'select', 'options' => 'orgUnits', 'rules' => 'permit_empty|is_natural_no_zero'],
                'sort_order' => ['label' => 'Order', 'type' => 'number', 'rules' => 'permit_empty|integer'],
            ],
        ],
        'site_blocks' => [
            'table'     => 'site_blocks',
            'label'     => 'Home page notices',
            'singular'  => 'Notice',
            'titleField'=> 'block_key',
            'scope'     => 'site',
            'localized' => true,
            'fixedRows' => true,
            'list'      => ['block_key'],
            'fields'    => [
                'body' => ['label' => 'Text', 'type' => 'markdown', 'rules' => 'permit_empty|max_length[5000]', 'help' => 'Only MOFAIC-approved emergency information. The area is hidden while this is empty.'],
            ],
        ],
    ];

    /**
     * @var array<string, string>
     */
    public array $articleCategories = [
        'news'             => 'News',
        'statement'        => 'Statement',
        'mission_activity' => 'Mission activity',
    ];

    /**
     * Mission categories in the confirmed MISSIONS hierarchy (brief section 2).
     * Values: label, direction and the public path segment under /missions.
     *
     * @var array<string, array{label: string, navKey: string, path: string}>
     */
    public array $missionCategories = [
        'abroad_embassy'               => ['label' => 'Missions Abroad: Embassy', 'navKey' => 'abroadEmbassies', 'path' => 'abroad/embassies'],
        'abroad_consulate'             => ['label' => 'Missions Abroad: Consulate', 'navKey' => 'abroadConsulates', 'path' => 'abroad/consulates'],
        'abroad_permanent_mission'     => ['label' => 'Missions Abroad: Permanent Mission', 'navKey' => 'abroadPermanentMissions', 'path' => 'abroad/permanent-missions'],
        'abroad_non_resident_mission'  => ['label' => 'Missions Abroad: Non-resident Mission', 'navKey' => 'abroadNonResidentMissions', 'path' => 'abroad/non-resident-missions'],
        'foreign_embassy'              => ['label' => 'Foreign Missions in South Sudan: Embassy', 'navKey' => 'foreignEmbassies', 'path' => 'in-south-sudan/embassies'],
        'foreign_international_agency' => ['label' => 'Foreign Missions in South Sudan: International Agency', 'navKey' => 'foreignInternationalAgencies', 'path' => 'in-south-sudan/international-agencies'],
    ];

    /**
     * @var array<string, string>
     */
    public array $pageSections = [
        'about-mfaic'       => 'About MFA&IC',
        'about-south-sudan' => 'About South Sudan',
        'services'          => 'Services',
    ];

    /**
     * Fixed text blocks: the home page emergency contacts and the official
     * contact information on CONTACTS. Only these rows exist; they cannot be
     * added or removed in the administration area.
     *
     * @var array<string, string>
     */
    public array $siteBlocks = [
        'emergency_contacts'  => 'Official emergency contacts',
        'contact_information' => 'Official contact information (CONTACTS page)',
    ];

    /**
     * Tag that places a news article in the home page's "Minister's recent
     * engagements" area (brief section 2).
     */
    public string $ministerEngagementTag = 'minister-engagements';

    /**
     * @return array<string, mixed>
     */
    public function type(string $key): array
    {
        return $this->types[$key] ?? [];
    }
}

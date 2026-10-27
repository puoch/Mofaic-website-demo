<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gate 3 content tables: pages, news and statements, galleries, archives,
 * missions and country coverage, media, revisions and small site lists.
 *
 * No Ministry content is created here. Structure pages come from
 * StructureSeeder; real content is entered by editors.
 */
class CreateContentTables extends Migration
{
    public function up(): void
    {
        $this->countries();
        $this->missions();
        $this->pages();
        $this->articles();
        $this->galleriesAndArchives();
        $this->media();
        $this->revisions();
        $this->siteLists();
    }

    public function down(): void
    {
        foreach ([
            'org_units', 'site_blocks', 'quick_links', 'social_accounts', 'content_revisions',
            'media_links', 'media', 'archive_items', 'galleries', 'article_tag', 'tags', 'articles',
            'pages', 'mission_coverage', 'missions', 'countries',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function audit(): array
    {
        return [
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function publishing(): array
    {
        return [
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'published_at' => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function countries(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'iso_code'   => ['type' => 'CHAR', 'constraint' => 2],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 190],
            'is_demo'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('iso_code');
        $this->forge->createTable('countries');
    }

    private function missions(): void
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'locale'                => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'slug'                  => ['type' => 'VARCHAR', 'constraint' => 190],
            'name'                  => ['type' => 'VARCHAR', 'constraint' => 255],
            'category'              => ['type' => 'VARCHAR', 'constraint' => 40],
            'location_country_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'represents_country_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'city'                  => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'address'               => ['type' => 'TEXT', 'null' => true],
            'phone'                 => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'email'                 => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'website'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'head_of_mission_title' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'head_of_mission_name'  => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'body'                  => ['type' => 'TEXT', 'null' => true],
            'is_demo'               => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ] + $this->publishing() + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['locale', 'slug']);
        $this->forge->addKey(['category', 'status']);
        $this->forge->createTable('missions');

        // Which countries a South Sudanese mission abroad serves. A mission
        // can serve countries where it is not located (non-resident).
        $this->forge->addField([
            'mission_id' => ['type' => 'INT', 'unsigned' => true],
            'country_id' => ['type' => 'INT', 'unsigned' => true],
            'coverage'   => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'resident'],
        ]);
        $this->forge->addPrimaryKey(['mission_id', 'country_id']);
        $this->forge->addKey('country_id');
        $this->forge->addForeignKey('mission_id', 'missions', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('country_id', 'countries', 'id', '', 'CASCADE');
        $this->forge->createTable('mission_coverage');
    }

    private function pages(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'locale'         => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'section'        => ['type' => 'VARCHAR', 'constraint' => 40],
            'parent_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'slug'           => ['type' => 'VARCHAR', 'constraint' => 190],
            'path'           => ['type' => 'VARCHAR', 'constraint' => 500],
            'title'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'summary'        => ['type' => 'TEXT', 'null' => true],
            'body'           => ['type' => 'TEXT', 'null' => true],
            'external_links' => ['type' => 'TEXT', 'null' => true],
            'department_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'sort_order'     => ['type' => 'INT', 'default' => 0],
            'is_system'      => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ] + $this->publishing() + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['locale', 'section', 'path']);
        $this->forge->addKey('parent_id');
        $this->forge->createTable('pages');
    }

    private function articles(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'locale'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'category'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'slug'          => ['type' => 'VARCHAR', 'constraint' => 190],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'summary'       => ['type' => 'TEXT', 'null' => true],
            'body'          => ['type' => 'TEXT', 'null' => true],
            'department_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'mission_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'is_featured'   => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ] + $this->publishing() + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['locale', 'slug']);
        $this->forge->addKey(['category', 'status', 'published_at']);
        $this->forge->addKey('mission_id');
        $this->forge->createTable('articles');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 190],
            'is_system'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('tags');

        $this->forge->addField([
            'article_id' => ['type' => 'INT', 'unsigned' => true],
            'tag_id'     => ['type' => 'INT', 'unsigned' => true],
        ]);
        $this->forge->addPrimaryKey(['article_id', 'tag_id']);
        $this->forge->addKey('tag_id');
        $this->forge->addForeignKey('article_id', 'articles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('tag_id', 'tags', 'id', '', 'CASCADE');
        $this->forge->createTable('article_tag');
    }

    private function galleriesAndArchives(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'locale'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'slug'          => ['type' => 'VARCHAR', 'constraint' => 190],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'summary'       => ['type' => 'TEXT', 'null' => true],
            'department_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ] + $this->publishing() + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['locale', 'slug']);
        $this->forge->createTable('galleries');

        // Archive classification is deferred (brief 6.2), so there is no
        // category column yet.
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'locale'             => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'slug'               => ['type' => 'VARCHAR', 'constraint' => 190],
            'title'              => ['type' => 'VARCHAR', 'constraint' => 255],
            'body'               => ['type' => 'TEXT', 'null' => true],
            'department_id'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'related_article_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'related_gallery_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ] + $this->publishing() + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['locale', 'slug']);
        $this->forge->createTable('archive_items');
    }

    private function media(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kind'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'source'        => ['type' => 'VARCHAR', 'constraint' => 20],
            'path'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'external_url'  => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'mime'          => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'size'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'alt_text'      => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('media');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'media_id'   => ['type' => 'INT', 'unsigned' => true],
            'owner_type' => ['type' => 'VARCHAR', 'constraint' => 40],
            'owner_id'   => ['type' => 'INT', 'unsigned' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['owner_type', 'owner_id']);
        $this->forge->addForeignKey('media_id', 'media', 'id', '', 'CASCADE');
        $this->forge->createTable('media_links');
    }

    private function revisions(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'content_type'    => ['type' => 'VARCHAR', 'constraint' => 40],
            'content_id'      => ['type' => 'INT', 'unsigned' => true],
            'revision_number' => ['type' => 'INT', 'unsigned' => true],
            'snapshot'        => ['type' => 'TEXT'],
            'note'            => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['content_type', 'content_id', 'revision_number']);
        $this->forge->createTable('content_revisions');
    }

    private function siteLists(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'platform'   => ['type' => 'VARCHAR', 'constraint' => 60],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('social_accounts');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'label'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 500],
            'sort_order' => ['type' => 'INT', 'default' => 0],
            'is_active'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
        ] + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('quick_links');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'block_key'  => ['type' => 'VARCHAR', 'constraint' => 60],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'body'       => ['type' => 'TEXT', 'null' => true],
        ] + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['block_key', 'locale']);
        $this->forge->createTable('site_blocks');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'locale'     => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'parent_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'head_title' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'sort_order' => ['type' => 'INT', 'default' => 0],
        ] + $this->audit());
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('org_units');
    }
}

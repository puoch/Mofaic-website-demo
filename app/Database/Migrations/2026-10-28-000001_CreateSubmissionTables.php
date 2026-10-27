<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gate 4 tables: public inquiries, diaspora registrations and contributions,
 * staff-only notes, newsletter subscriptions, the outgoing mail queue,
 * notification rules and Diaspora by Country pages.
 *
 * Submissions hold personal data (brief section 4). There are no columns for
 * passport, national ID, financial or banking details and no file storage
 * (brief sections 2 and 5).
 */
class CreateSubmissionTables extends Migration
{
    public function up(): void
    {
        $this->inquiries();
        $this->diaspora();
        $this->notes();
        $this->newsletter();
        $this->mail();
        $this->countryPages();
    }

    public function down(): void
    {
        foreach ([
            'diaspora_country_pages', 'notification_rules', 'mail_queue', 'newsletter_sends',
            'newsletter_subscriptions', 'submission_notes', 'diaspora_contributions',
            'diaspora_registrations', 'inquiries',
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function handling(): array
    {
        return [
            'status'      => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'new'],
            'assigned_to' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ];
    }

    private function inquiries(): void
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 20],
            'locale'    => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'email'     => ['type' => 'VARCHAR', 'constraint' => 190],
            'phone'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'subject'   => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'message'   => ['type' => 'TEXT'],
        ] + $this->handling());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->createTable('inquiries');
    }

    private function diaspora(): void
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reference'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'locale'            => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'name'              => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'address'           => ['type' => 'TEXT', 'null' => true],
            'city'              => ['type' => 'VARCHAR', 'constraint' => 190],
            'locality'          => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'country_id'        => ['type' => 'INT', 'unsigned' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone'             => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'inquiry_nature'    => ['type' => 'TEXT', 'null' => true],
            'consular_help'     => ['type' => 'TEXT', 'null' => true],
            'chosen_mission_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'mission_chosen_at' => ['type' => 'DATETIME', 'null' => true],
        ] + $this->handling());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('country_id');
        $this->forge->createTable('diaspora_registrations');

        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reference' => ['type' => 'VARCHAR', 'constraint' => 20],
            'locale'    => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'category'  => ['type' => 'VARCHAR', 'constraint' => 40],
            'message'   => ['type' => 'TEXT'],
            'name'      => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'email'     => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone'     => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
        ] + $this->handling());
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('reference');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->createTable('diaspora_contributions');
    }

    private function notes(): void
    {
        // Internal notes are never shown publicly or sent by email (brief section 3).
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'submission_type' => ['type' => 'VARCHAR', 'constraint' => 20],
            'submission_id'   => ['type' => 'INT', 'unsigned' => true],
            'user_id'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'note'            => ['type' => 'TEXT'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['submission_type', 'submission_id']);
        $this->forge->createTable('submission_notes');
    }

    private function newsletter(): void
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'email'              => ['type' => 'VARCHAR', 'constraint' => 190],
            'locale'             => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'source'             => ['type' => 'VARCHAR', 'constraint' => 40],
            'confirm_token_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
            'confirm_sent_at'    => ['type' => 'DATETIME', 'null' => true],
            'confirmed_at'       => ['type' => 'DATETIME', 'null' => true],
            'unsubscribed_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('confirm_token_hash');
        $this->forge->createTable('newsletter_subscriptions');

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'article_id'   => ['type' => 'INT', 'unsigned' => true],
            'queued_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'sent_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('article_id');
        $this->forge->createTable('newsletter_sends');
    }

    private function mail(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'purpose'    => ['type' => 'VARCHAR', 'constraint' => 40],
            'to_email'   => ['type' => 'VARCHAR', 'constraint' => 190],
            'subject'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'body'       => ['type' => 'TEXT'],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'queued'],
            'attempts'   => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'last_error' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'sent_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'id']);
        $this->forge->createTable('mail_queue');

        $this->forge->addField([
            'id'              => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'submission_type' => ['type' => 'VARCHAR', 'constraint' => 20],
            'recipients'      => ['type' => 'TEXT'],
            'subject'         => ['type' => 'VARCHAR', 'constraint' => 190],
            'intro'           => ['type' => 'TEXT', 'null' => true],
            'is_active'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'      => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('submission_type');
        $this->forge->createTable('notification_rules');
    }

    private function countryPages(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'locale'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'en'],
            'country_id'    => ['type' => 'INT', 'unsigned' => true],
            'slug'          => ['type' => 'VARCHAR', 'constraint' => 190],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 255],
            'summary'       => ['type' => 'TEXT', 'null' => true],
            'body'          => ['type' => 'TEXT', 'null' => true],
            'department_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'draft'],
            'published_at'  => ['type' => 'DATETIME', 'null' => true],
            'created_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'updated_by'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['locale', 'slug']);
        $this->forge->addUniqueKey(['locale', 'country_id']);
        $this->forge->createTable('diaspora_country_pages');
    }
}

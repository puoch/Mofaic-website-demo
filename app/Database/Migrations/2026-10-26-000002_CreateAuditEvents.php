<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Append-only audit log of administration activity (brief section 4).
 * Retention period is deferred to MOFAIC policy (brief section 6, item 13).
 */
class CreateAuditEvents extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'occurred_at'   => ['type' => 'DATETIME'],
            'actor_user_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'event'         => ['type' => 'VARCHAR', 'constraint' => 80],
            'subject_type'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'subject_id'    => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'ip_address'    => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'details'       => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('occurred_at');
        $this->forge->addKey('event');
        $this->forge->addKey('actor_user_id');
        $this->forge->createTable('audit_events');
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_events', true);
    }
}

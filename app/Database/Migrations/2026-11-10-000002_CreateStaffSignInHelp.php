<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Staff sign-in help, requested by the project owner on 2026-11-10:
 *
 * - staff_password_tokens: single-use links for "Forgot password" and for
 *   setting the first password of an approved account. Only a SHA-256 hash
 *   of each token is stored.
 * - staff_account_requests: "Request an account" submissions. They give no
 *   access until a Super Administrator approves one and chooses the role.
 */
class CreateStaffSignInHelp extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'purpose'    => ['type' => 'VARCHAR', 'constraint' => 20],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'expires_at' => ['type' => 'DATETIME'],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash');
        $this->forge->addKey('user_id');
        $this->forge->createTable('staff_password_tokens');

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'full_name'  => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 254],
            'office'     => ['type' => 'VARCHAR', 'constraint' => 160],
            'job_title'  => ['type' => 'VARCHAR', 'constraint' => 120],
            'reason'     => ['type' => 'TEXT'],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'user_id'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'decided_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'decided_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('email');
        $this->forge->createTable('staff_account_requests');
    }

    public function down(): void
    {
        $this->forge->dropTable('staff_account_requests', true);
        $this->forge->dropTable('staff_password_tokens', true);
    }
}

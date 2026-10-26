<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ministry departments and Department Editor membership (brief section 3:
 * editors may belong to several departments).
 *
 * No departments are created here. The real list must come from the Ministry.
 */
class CreateDepartments extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'slug'        => ['type' => 'VARCHAR', 'constraint' => 80],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 190],
            'is_diaspora' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_demo'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('slug');
        $this->forge->createTable('departments');

        $this->forge->addField([
            'department_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id'       => ['type' => 'INT', 'unsigned' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey(['department_id', 'user_id']);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('department_id', 'departments', 'id', '', 'CASCADE');
        $this->forge->createTable('department_user');
    }

    public function down(): void
    {
        $this->forge->dropTable('department_user', true);
        $this->forge->dropTable('departments', true);
    }
}

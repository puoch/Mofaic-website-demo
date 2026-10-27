<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Full-text index for News & Press search (PD-15). MySQL only: on other
 * databases, such as SQLite in the tests, search uses a plain text match.
 */
class AddArticleSearchIndex extends Migration
{
    public function up(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        $this->db->query('ALTER TABLE ' . $this->db->protectIdentifiers('articles', true) . ' ADD FULLTEXT INDEX articles_search (title, summary, body)');
    }

    public function down(): void
    {
        if ($this->db->DBDriver !== 'MySQLi') {
            return;
        }

        $this->db->query('ALTER TABLE ' . $this->db->protectIdentifiers('articles', true) . ' DROP INDEX articles_search');
    }
}

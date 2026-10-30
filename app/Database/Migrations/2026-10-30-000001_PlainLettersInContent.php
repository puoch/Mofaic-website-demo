<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use App\Libraries\ContentService;
use CodeIgniter\Database\Migration;
use Config\ContentTypes;

/**
 * Cleans "fancy font" letters pasted before saving did it (see
 * ContentService::plainLetters), so existing posts also show in the site
 * font. Only text, textarea and markdown fields change.
 */
class PlainLettersInContent extends Migration
{
    public function up(): void
    {
        foreach (config(ContentTypes::class)->types as $definition) {
            $columns = array_keys(array_filter(
                $definition['fields'],
                static fn (array $field): bool => in_array($field['type'], ['text', 'textarea', 'markdown'], true),
            ));

            if ($columns === [] || ! $this->db->tableExists($definition['table'])) {
                continue;
            }

            $rows = $this->db->table($definition['table'])->select(['id', ...$columns])->get()->getResultArray();

            foreach ($rows as $row) {
                $changes = [];
                foreach ($columns as $column) {
                    if (is_string($row[$column]) && ($plain = ContentService::plainLetters($row[$column])) !== $row[$column]) {
                        $changes[$column] = $plain;
                    }
                }
                if ($changes !== []) {
                    $this->db->table($definition['table'])->where('id', $row['id'])->update($changes);
                }
            }
        }
    }

    public function down(): void
    {
        // The original fancy letters are not kept.
    }
}

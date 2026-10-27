<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Config\ContentTypes;
use InvalidArgumentException;
use JsonException;

/**
 * Saves, publishes and restores content of every type in Config\ContentTypes.
 *
 * Every save of a type with 'revisions' stores a full snapshot in
 * content_revisions (all revisions are kept until MOFAIC sets a retention
 * policy, PD-13). Every change is written to the audit log, which is also the
 * publishing log (brief section 3).
 *
 * Callers validate input and check ContentPolicy before calling.
 */
class ContentService
{
    /**
     * @var array<string, list<string>>
     */
    private array $columns = [];

    public function __construct(
        private readonly BaseConnection $db,
        private readonly ContentTypes $types,
        private readonly AuditLog $audit,
        private readonly string $defaultLocale = 'en',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(string $type): array
    {
        $definition = $this->types->type($type);

        if ($definition === []) {
            throw new InvalidArgumentException("Unknown content type: {$type}");
        }

        return $definition;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $type, int $id): ?array
    {
        $row = $this->db->table($this->definition($type)['table'])->where('id', $id)->get()->getRowArray();

        if ($row === null) {
            return null;
        }

        if (isset($this->definition($type)['fields']['tags'])) {
            $row['tags'] = implode(', ', $this->tagNames($id));
        }

        return $row;
    }

    /**
     * Creates ($id null) or updates a record and returns its id.
     *
     * @param array<string, mixed> $input Validated form values
     */
    public function save(string $type, ?int $id, array $input, ?int $userId, ?string $note = null): int
    {
        $definition = $this->definition($type);
        $table      = $definition['table'];
        $existing   = $id === null ? null : $this->find($type, $id);

        if ($id !== null && $existing === null) {
            throw new InvalidArgumentException("No {$type} record {$id}");
        }

        $data = $this->normalise($definition, $input, $existing);

        if (! empty($definition['localized'])) {
            $data['locale'] = $existing['locale'] ?? ($input['locale'] ?? $this->defaultLocale);
        }

        if (isset($definition['fields']['slug'])) {
            $data['slug'] = $this->uniqueSlug($type, $data, $existing);
        }

        if ($type === 'pages') {
            $data['path'] = $this->pagePath($data, $existing);
        }

        $now = Time::now('UTC')->toDateTimeString();
        $this->stamp($table, $data, 'updated_at', $now);
        $this->stamp($table, $data, 'updated_by', $userId);

        if ($existing === null) {
            $this->stamp($table, $data, 'created_at', $now);
            $this->stamp($table, $data, 'created_by', $userId);
            $this->db->table($table)->insert($data);
            $id = (int) $this->db->insertID();
        } else {
            $this->db->table($table)->where('id', $id)->update($data);
        }

        if (isset($definition['fields']['tags'])) {
            $this->syncTags($id, (string) ($input['tags'] ?? ''));
        }

        if ($type === 'pages' && $existing !== null && $existing['path'] !== $data['path']) {
            $this->movePageDescendants($id, $data['path']);
        }

        if (! empty($definition['revisions'])) {
            $this->storeRevision($type, $id, $userId, $note);
        }

        $this->audit->record(
            $existing === null ? 'content.created' : 'content.updated',
            $userId,
            $type,
            $id,
            ['title' => $this->titleOf($type, $id), 'note' => $note],
        );

        return $id;
    }

    public function publish(string $type, int $id, ?int $userId): void
    {
        $record = $this->requireStatusRecord($type, $id);
        $now    = Time::now('UTC')->toDateTimeString();

        $this->db->table($this->definition($type)['table'])->where('id', $id)->update([
            'status'       => 'published',
            'published_at' => $record['published_at'] ?? $now,
            'updated_by'   => $userId,
            'updated_at'   => $now,
        ]);

        $this->audit->record('content.published', $userId, $type, $id, ['title' => $this->titleOf($type, $id)]);
    }

    public function unpublish(string $type, int $id, ?int $userId): void
    {
        $this->requireStatusRecord($type, $id);

        $this->db->table($this->definition($type)['table'])->where('id', $id)->update([
            'status'     => 'draft',
            'updated_by' => $userId,
            'updated_at' => Time::now('UTC')->toDateTimeString(),
        ]);

        $this->audit->record('content.unpublished', $userId, $type, $id, ['title' => $this->titleOf($type, $id)]);
    }

    /**
     * Only types without publishing status can be deleted; published content
     * is unpublished instead so its history is kept.
     */
    public function delete(string $type, int $id, ?int $userId): void
    {
        $definition = $this->definition($type);
        $record     = $this->find($type, $id);

        if ($record === null) {
            return;
        }

        if (! empty($definition['status']) || ! empty($definition['fixedRows']) || ! empty($record['is_system'])) {
            throw new InvalidArgumentException('This record cannot be deleted.');
        }

        $title = $this->titleOf($type, $id);
        $this->db->table($definition['table'])->where('id', $id)->delete();
        $this->audit->record('content.deleted', $userId, $type, $id, ['title' => $title]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function revisions(string $type, int $id): array
    {
        return $this->db->table('content_revisions')
            ->where(['content_type' => $type, 'content_id' => $id])
            ->orderBy('revision_number', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function revision(string $type, int $id, int $revisionId): ?array
    {
        $row = $this->db->table('content_revisions')
            ->where(['id' => $revisionId, 'content_type' => $type, 'content_id' => $id])
            ->get()
            ->getRowArray();

        if ($row === null) {
            return null;
        }

        $row['data'] = json_decode($row['snapshot'], true, 512, JSON_THROW_ON_ERROR);

        return $row;
    }

    /**
     * Brings back the content of an earlier revision as a new revision.
     * Publishing status is not changed by a restore.
     */
    public function restore(string $type, int $id, int $revisionId, ?int $userId): void
    {
        $revision = $this->revision($type, $id, $revisionId);

        if ($revision === null) {
            throw new InvalidArgumentException('Revision not found.');
        }

        $note = 'Restored from revision ' . $revision['revision_number'];
        $this->save($type, $id, $revision['data'], $userId, $note);
        $this->audit->record('content.restored', $userId, $type, $id, [
            'title'    => $this->titleOf($type, $id),
            'revision' => (int) $revision['revision_number'],
        ]);
    }

    /**
     * The minister's-engagements tag and other system tags.
     */
    public function ensureTag(string $slug, string $name, bool $system = false): int
    {
        $row = $this->db->table('tags')->where('slug', $slug)->get()->getRowArray();

        if ($row !== null) {
            return (int) $row['id'];
        }

        $now = Time::now('UTC')->toDateTimeString();
        $this->db->table('tags')->insert([
            'slug' => $slug, 'name' => $name, 'is_system' => $system ? 1 : 0, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return (int) $this->db->insertID();
    }

    public static function slugify(string $text): string
    {
        helper(['url', 'text']);

        return trim(url_title(convert_accented_characters($text), '-', true), '-');
    }

    /**
     * @return list<string>
     */
    private function tagNames(int $articleId): array
    {
        $rows = $this->db->table('article_tag')
            ->select('tags.name')
            ->join('tags', 'tags.id = article_tag.tag_id')
            ->where('article_tag.article_id', $articleId)
            ->orderBy('tags.name')
            ->get()
            ->getResultArray();

        return array_column($rows, 'name');
    }

    private function syncTags(int $articleId, string $tags): void
    {
        $ids    = [];
        $byName = [];

        foreach ($this->db->table('tags')->select('id, name')->get()->getResultArray() as $tag) {
            $byName[mb_strtolower($tag['name'])] = (int) $tag['id'];
        }

        foreach (array_filter(array_map('trim', explode(',', $tags))) as $name) {
            $slug = self::slugify($name);

            if ($slug === '') {
                continue;
            }

            // Reuse an existing tag with the same name, so typing
            // "Minister's engagements" finds the system tag.
            $existing = $byName[mb_strtolower($name)] ?? null;

            $ids[$slug] = $existing ?? $this->ensureTag($slug, mb_substr($name, 0, 190));
        }

        $this->db->table('article_tag')->where('article_id', $articleId)->delete();

        foreach (array_unique($ids) as $tagId) {
            $this->db->table('article_tag')->insert(['article_id' => $articleId, 'tag_id' => $tagId]);
        }
    }

    /**
     * @param array<string, mixed>      $definition
     * @param array<string, mixed>      $input
     * @param array<string, mixed>|null $existing
     *
     * @return array<string, mixed>
     */
    private function normalise(array $definition, array $input, ?array $existing): array
    {
        $data     = [];
        $isSystem = ! empty($existing['is_system']);

        foreach ($definition['fields'] as $name => $field) {
            if ($field['type'] === 'tags') {
                continue;
            }

            if ($isSystem && ! empty($field['lockedForSystem'])) {
                continue;
            }

            $value = $input[$name] ?? null;

            $data[$name] = match ($field['type']) {
                'checkbox' => ! empty($value) ? 1 : 0,
                'number'   => $value === null || $value === '' ? 0 : (int) $value,
                default    => is_string($value) && trim($value) === '' ? null : (is_string($value) ? trim($value) : $value),
            };
        }

        return $data;
    }

    /**
     * @param array<string, mixed>      $data
     * @param array<string, mixed>|null $existing
     */
    private function uniqueSlug(string $type, array $data, ?array $existing): string
    {
        $definition = $this->definition($type);

        if (! array_key_exists('slug', $data)) {
            return (string) $existing['slug'];
        }

        $base = self::slugify((string) ($data['slug'] ?? $data[$definition['slugFrom'] ?? 'title'] ?? ''));

        if ($base === '') {
            $base = rtrim(substr($type, 0, -1), '_') . '-' . date('YmdHis');
        }

        $base = substr($base, 0, 180);
        $slug = $base;

        for ($n = 2; $this->slugTaken($type, $slug, $data, $existing); $n++) {
            $slug = $base . '-' . $n;
        }

        return $slug;
    }

    /**
     * @param array<string, mixed>      $data
     * @param array<string, mixed>|null $existing
     */
    private function slugTaken(string $type, string $slug, array $data, ?array $existing): bool
    {
        $definition = $this->definition($type);
        $builder    = $this->db->table($definition['table']);

        if ($type === 'pages') {
            $builder->where('section', $data['section'] ?? $existing['section'])
                ->where('path', $this->joinPath($this->parentPath($data, $existing), $slug));
        } else {
            $builder->where('slug', $slug);
        }

        if (! empty($definition['localized'])) {
            $builder->where('locale', $data['locale']);
        }

        if ($existing !== null) {
            $builder->where('id !=', $existing['id']);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * @param array<string, mixed>      $data
     * @param array<string, mixed>|null $existing
     */
    private function pagePath(array $data, ?array $existing): string
    {
        return $this->joinPath($this->parentPath($data, $existing), $data['slug'] ?? $existing['slug']);
    }

    /**
     * @param array<string, mixed>      $data
     * @param array<string, mixed>|null $existing
     */
    private function parentPath(array $data, ?array $existing): string
    {
        $parentId = array_key_exists('parent_id', $data) ? $data['parent_id'] : ($existing['parent_id'] ?? null);

        if ($parentId === null) {
            return '';
        }

        $parent = $this->db->table('pages')->where('id', $parentId)->get()->getRowArray();

        if ($parent === null) {
            throw new InvalidArgumentException('The parent page does not exist.');
        }

        $section = $data['section'] ?? $existing['section'];

        if ($parent['section'] !== $section) {
            throw new InvalidArgumentException('The parent page must be in the same section.');
        }

        if ($existing !== null && ((int) $parent['id'] === (int) $existing['id'] || str_starts_with($parent['path'] . '/', $existing['path'] . '/'))) {
            throw new InvalidArgumentException('A page cannot be placed under itself.');
        }

        return $parent['path'];
    }

    private function joinPath(string $parentPath, string $slug): string
    {
        return $parentPath === '' ? $slug : $parentPath . '/' . $slug;
    }

    private function movePageDescendants(int $pageId, string $newPath): void
    {
        foreach ($this->db->table('pages')->where('parent_id', $pageId)->get()->getResultArray() as $child) {
            $childPath = $newPath . '/' . $child['slug'];
            $this->db->table('pages')->where('id', $child['id'])->update(['path' => $childPath]);
            $this->movePageDescendants((int) $child['id'], $childPath);
        }
    }

    /**
     * @throws JsonException
     */
    private function storeRevision(string $type, int $id, ?int $userId, ?string $note): void
    {
        $definition = $this->definition($type);
        $record     = $this->find($type, $id);
        $snapshot   = [];

        foreach (array_keys($definition['fields']) as $field) {
            $snapshot[$field] = $record[$field] ?? null;
        }

        $last = $this->db->table('content_revisions')
            ->selectMax('revision_number', 'n')
            ->where(['content_type' => $type, 'content_id' => $id])
            ->get()
            ->getRowArray();

        $this->db->table('content_revisions')->insert([
            'content_type'    => $type,
            'content_id'      => $id,
            'revision_number' => (int) ($last['n'] ?? 0) + 1,
            'snapshot'        => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'note'            => $note,
            'created_by'      => $userId,
            'created_at'      => Time::now('UTC')->toDateTimeString(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requireStatusRecord(string $type, int $id): array
    {
        if (empty($this->definition($type)['status'])) {
            throw new InvalidArgumentException("{$type} has no publishing status.");
        }

        $record = $this->find($type, $id);

        if ($record === null) {
            throw new InvalidArgumentException("No {$type} record {$id}");
        }

        return $record;
    }

    private function titleOf(string $type, int $id): ?string
    {
        $field = $this->definition($type)['titleField'];
        $row   = $this->db->table($this->definition($type)['table'])->select($field)->where('id', $id)->get()->getRowArray();

        return $row[$field] ?? null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function stamp(string $table, array &$data, string $column, mixed $value): void
    {
        $this->columns[$table] ??= $this->db->getFieldNames($table);

        if (in_array($column, $this->columns[$table], true)) {
            $data[$column] = $value;
        }
    }
}

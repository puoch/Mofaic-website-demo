<?php

declare(strict_types=1);

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\I18n\Time;
use Config\Media;
use InvalidArgumentException;

/**
 * Public media: uploaded images, PDF documents, audio and video, and links to
 * audio or video on official external channels.
 *
 * Files are checked by their detected content type, not only by the name the
 * browser sent, and stored under a random name.
 */
class MediaService
{
    public function __construct(
        private readonly BaseConnection $db,
        private readonly Media $config,
        private readonly AuditLog $audit,
        private readonly string $publicRoot = FCPATH,
    ) {
    }

    /**
     * Checks an upload against the allowed types and size for its kind.
     * Returns an error message, or null when the file is acceptable.
     */
    public function uploadError(?UploadedFile $file, string $kind): ?string
    {
        $rules = $this->config->kinds[$kind] ?? null;

        if ($rules === null) {
            return 'Choose what kind of file this is.';
        }

        if ($file === null || ! $file->isValid()) {
            return 'The file did not upload. Try again.';
        }

        $extension = strtolower($file->getClientExtension());
        $mime      = $file->getMimeType();

        if (! in_array($extension, $rules['extensions'], true) || ! in_array($mime, $rules['mimes'], true)) {
            return $rules['label'] . ' files must be ' . strtoupper(implode(', ', $rules['extensions'])) . '.';
        }

        if ($file->getSizeByUnit('kb') > $rules['maxKb']) {
            return $rules['label'] . ' files can be at most ' . round($rules['maxKb'] / 1024) . ' MB.';
        }

        return null;
    }

    /**
     * Stores a checked upload and links it to its owner record.
     */
    public function storeUpload(UploadedFile $file, string $kind, string $title, ?string $altText, string $ownerType, int $ownerId, ?int $userId): int
    {
        if (($error = $this->uploadError($file, $kind)) !== null) {
            throw new InvalidArgumentException($error);
        }

        $folder = $this->config->publicFolder . '/' . date('Y/m');
        $mime   = $file->getMimeType();
        $size   = $file->getSize();
        $name   = $file->getRandomName();
        $file->move(rtrim($this->publicRoot, '/') . '/' . $folder, $name);

        return $this->insert([
            'kind'          => $kind,
            'source'        => 'upload',
            'path'          => $folder . '/' . $name,
            'original_name' => mb_substr($file->getClientName(), 0, 255),
            'mime'          => $mime,
            'size'          => $size,
            'title'         => $title,
            'alt_text'      => $altText,
        ], $ownerType, $ownerId, $userId);
    }

    /**
     * Links audio or video published on an official external channel.
     */
    public function storeExternal(string $kind, string $url, string $title, string $ownerType, int $ownerId, ?int $userId): int
    {
        if (! in_array($kind, $this->config->externalKinds, true)) {
            throw new InvalidArgumentException('Only audio and video can be linked from an external channel.');
        }

        if (! str_starts_with($url, 'https://') || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('External media must have an https:// address.');
        }

        return $this->insert([
            'kind'         => $kind,
            'source'       => 'external',
            'external_url' => $url,
            'title'        => $title,
        ], $ownerType, $ownerId, $userId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function forOwner(string $ownerType, int $ownerId, ?string $kind = null): array
    {
        $builder = $this->db->table('media_links')
            ->select('media.*, media_links.id AS link_id, media_links.sort_order')
            ->join('media', 'media.id = media_links.media_id')
            ->where(['media_links.owner_type' => $ownerType, 'media_links.owner_id' => $ownerId])
            ->orderBy('media_links.sort_order')
            ->orderBy('media_links.id');

        if ($kind !== null) {
            $builder->where('media.kind', $kind);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * First image of each owner, for listings.
     *
     * @param list<int> $ownerIds
     *
     * @return array<int, array<string, mixed>>
     */
    public function firstImages(string $ownerType, array $ownerIds): array
    {
        if ($ownerIds === []) {
            return [];
        }

        $rows = $this->db->table('media_links')
            ->select('media.*, media_links.owner_id')
            ->join('media', 'media.id = media_links.media_id')
            ->where('media_links.owner_type', $ownerType)
            ->whereIn('media_links.owner_id', $ownerIds)
            ->where('media.kind', 'image')
            ->orderBy('media_links.sort_order')
            ->orderBy('media_links.id')
            ->get()
            ->getResultArray();

        $first = [];
        foreach ($rows as $row) {
            $first[(int) $row['owner_id']] ??= $row;
        }

        return $first;
    }

    /**
     * Removes a media item from its owner. The file is deleted when nothing
     * else uses it.
     */
    public function detach(int $linkId, string $ownerType, int $ownerId, ?int $userId): void
    {
        $link = $this->db->table('media_links')
            ->where(['id' => $linkId, 'owner_type' => $ownerType, 'owner_id' => $ownerId])
            ->get()
            ->getRowArray();

        if ($link === null) {
            return;
        }

        $this->db->table('media_links')->where('id', $linkId)->delete();

        if ($this->db->table('media_links')->where('media_id', $link['media_id'])->countAllResults() === 0) {
            $media = $this->db->table('media')->where('id', $link['media_id'])->get()->getRowArray();

            if ($media !== null && $media['path'] !== null) {
                $file = rtrim($this->publicRoot, '/') . '/' . $media['path'];

                if (is_file($file)) {
                    unlink($file);
                }
            }

            $this->db->table('media')->where('id', $link['media_id'])->delete();
        }

        $this->audit->record('media.removed', $userId, $ownerType, $ownerId, ['media_id' => (int) $link['media_id']]);
    }

    public static function url(array $media): string
    {
        return $media['source'] === 'external' ? (string) $media['external_url'] : base_url((string) $media['path']);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function insert(array $row, string $ownerType, int $ownerId, ?int $userId): int
    {
        $row['created_by'] = $userId;
        $row['created_at'] = Time::now('UTC')->toDateTimeString();
        $this->db->table('media')->insert($row);
        $mediaId = (int) $this->db->insertID();

        $next = $this->db->table('media_links')
            ->selectMax('sort_order', 'n')
            ->where(['owner_type' => $ownerType, 'owner_id' => $ownerId])
            ->get()
            ->getRowArray();

        $this->db->table('media_links')->insert([
            'media_id'   => $mediaId,
            'owner_type' => $ownerType,
            'owner_id'   => $ownerId,
            'sort_order' => (int) ($next['n'] ?? 0) + 1,
        ]);

        $this->audit->record('media.added', $userId, $ownerType, $ownerId, ['media_id' => $mediaId, 'kind' => $row['kind']]);

        return $mediaId;
    }
}

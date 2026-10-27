<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Public media uploads (images, documents, audio, video).
 *
 * The types and size limits below are proposed default PD-12. The Ministry has
 * not approved first-release media limits yet (brief section 6, item 4).
 *
 * Only public media is stored here, under public/uploads. Private
 * submission files are never stored in this location (brief section 4).
 */
class Media extends BaseConfig
{
    /**
     * Folder under FCPATH (public/) where uploads are stored.
     */
    public string $publicFolder = 'uploads';

    /**
     * @var array<string, array{label: string, extensions: list<string>, mimes: list<string>, maxKb: int}>
     */
    public array $kinds = [
        'image' => [
            'label'      => 'Image',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'mimes'      => ['image/jpeg', 'image/png', 'image/webp'],
            'maxKb'      => 10 * 1024,
        ],
        'document' => [
            'label'      => 'PDF document',
            'extensions' => ['pdf'],
            'mimes'      => ['application/pdf'],
            'maxKb'      => 20 * 1024,
        ],
        'audio' => [
            'label'      => 'Audio (MP3)',
            'extensions' => ['mp3'],
            'mimes'      => ['audio/mpeg', 'audio/mp3'],
            'maxKb'      => 50 * 1024,
        ],
        'video' => [
            'label'      => 'Video (MP4)',
            'extensions' => ['mp4'],
            'mimes'      => ['video/mp4'],
            'maxKb'      => 200 * 1024,
        ],
    ];

    /**
     * Video and audio can also be linked from official external channels.
     * They are shown as links, not embedded players: embedding a third-party
     * player sends visitor data to that provider and needs Ministry approval
     * and a Content Security Policy change.
     *
     * @var list<string>
     */
    public array $externalKinds = ['video', 'audio'];

    public function __construct()
    {
        parent::__construct();

        // A host may refuse large files whatever PHP allows (Config\Hosting).
        $capKb = (new Hosting())->maxUploadMegabytes * 1024;

        if ($capKb > 0) {
            foreach ($this->kinds as $kind => $rules) {
                $this->kinds[$kind]['maxKb'] = min($rules['maxKb'], $capKb);
            }
        }
    }
}

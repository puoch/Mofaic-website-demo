<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\AuditLog;
use App\Libraries\MediaService;
use CodeIgniter\HTTP\Files\UploadedFile;
use Config\Media;
use InvalidArgumentException;
use Tests\Support\StaffTestCase;

/**
 * Upload checks by detected type and size, external links, and removal
 * (MED-01 to MED-04, PD-12 limits).
 */
final class MediaServiceTest extends StaffTestCase
{
    private string $root;
    private MediaService $media;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root  = sys_get_temp_dir() . '/mofaic-media-' . bin2hex(random_bytes(4));
        mkdir($this->root);
        $this->media = new MediaService($this->db, config(Media::class), new AuditLog($this->db), $this->root);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->root));
        parent::tearDown();
    }

    public function testAcceptsAPngAndStoresItUnderARandomName(): void
    {
        $file = $this->fakeUpload('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true));

        $id  = $this->media->storeUpload($file, 'image', 'A photo', 'Alt text', 'articles', 7, null);
        $row = $this->db->table('media')->where('id', $id)->get()->getRowArray();

        $this->assertSame('image/png', $row['mime']);
        $this->assertSame('photo.png', $row['original_name']);
        $this->assertStringNotContainsString('photo', $row['path']);
        $this->assertFileExists($this->root . '/' . $row['path']);
        $this->assertSame(1, $this->db->table('audit_events')->where('event', 'media.added')->countAllResults());
    }

    public function testRejectsAFileWhoseContentDoesNotMatchItsExtension(): void
    {
        $file = $this->fakeUpload('report.pdf', "<?php echo 'not a pdf';");

        $this->assertSame('PDF document files must be PDF.', $this->media->uploadError($file, 'document'));
        $this->expectException(InvalidArgumentException::class);
        $this->media->storeUpload($file, 'document', 'Report', null, 'articles', 7, null);
    }

    public function testRejectsUnknownKinds(): void
    {
        $this->assertSame('Choose what kind of file this is.', $this->media->uploadError($this->fakeUpload('a.png', 'x'), 'script'));
    }

    public function testExternalLinksMustBeHttpsAudioOrVideo(): void
    {
        $id = $this->media->storeExternal('video', 'https://video.example.invalid/watch/1', 'Video', 'articles', 7, null);
        $this->assertSame('external', $this->db->table('media')->where('id', $id)->get()->getRow()->source);

        foreach ([['video', 'http://video.example.invalid/1'], ['video', 'javascript:alert(1)'], ['image', 'https://img.example.invalid/1.png']] as [$kind, $url]) {
            try {
                $this->media->storeExternal($kind, $url, 'Bad', 'articles', 7, null);
                $this->fail("Accepted {$kind} {$url}");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testDetachDeletesTheFileAndRecord(): void
    {
        $file = $this->fakeUpload('photo.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=', true));
        $id   = $this->media->storeUpload($file, 'image', 'A photo', null, 'articles', 7, null);
        $path = $this->root . '/' . $this->db->table('media')->where('id', $id)->get()->getRow()->path;
        $link = (int) $this->db->table('media_links')->where('media_id', $id)->get()->getRow()->id;

        $this->media->detach($link, 'articles', 8, null);
        $this->assertFileExists($path, 'A link belonging to another owner must not be removed.');

        $this->media->detach($link, 'articles', 7, null);
        $this->assertFileDoesNotExist($path);
        $this->assertSame(0, $this->db->table('media')->countAllResults());
        $this->assertSame(1, $this->db->table('audit_events')->where('event', 'media.removed')->countAllResults());
    }

    private function fakeUpload(string $name, string $bytes): UploadedFile
    {
        $tmp = tempnam($this->root, 'up');
        file_put_contents($tmp, $bytes);

        return new class ($tmp, $name, 'application/octet-stream', strlen($bytes), UPLOAD_ERR_OK) extends UploadedFile {
            public function isValid(): bool
            {
                return true;
            }

            public function move(string $targetPath, ?string $name = null, bool $overwrite = false): bool
            {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0775, true);
                }

                return rename($this->getTempName(), rtrim($targetPath, '/') . '/' . $name);
            }
        };
    }
}

<?php

namespace App\Tests\Service;

use App\Entity\File;
use App\Entity\FileType;
use App\Service\FileUploadService;
use App\Service\ImageProcessor;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Exercises the generic upload pipeline end to end: type detection, on-disk
 * storage layout, the webp + thumbnail pipeline for images, and the
 * missing-file placeholder fallback. Builds FileUploadService directly
 * (rather than fetching it from the container) since it has no consumer
 * yet in this step and would otherwise be compiled away as unused.
 */
class FileUploadServiceTest extends KernelTestCase
{
    private string $uploadDir;
    private FileUploadService $service;

    /** @var list<string> absolute paths written during the test, removed in tearDown */
    private array $writtenPaths = [];

    protected function setUp(): void
    {
        self::bootKernel();
        $this->uploadDir = \dirname((string) self::getContainer()->getParameter('kernel.project_dir')).'/upload';
        $this->service = new FileUploadService(
            $this->uploadDir,
            new ImageProcessor(),
            self::getContainer()->get(EntityManagerInterface::class),
        );
    }

    protected function tearDown(): void
    {
        self::getContainer()->get(Connection::class)->executeStatement('DELETE FROM file');

        foreach ($this->writtenPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function testUploadingAnImageGeneratesWebpAndFourThumbnails(): void
    {
        $file = $this->service->upload($this->makeImageUpload(600, 400), 'A test image');

        self::assertSame(FileType::Image, $file->getType());
        self::assertSame(600, $file->getWidth());
        self::assertSame(400, $file->getHeight());
        self::assertNotNull($file->getId());
        self::assertNotNull($file->getSource());
        self::assertStringStartsWith('/upload/img/source/', $file->getSource());
        self::assertStringStartsWith('/upload/img/webp/', $file->getFile());
        self::assertStringEndsWith('.webp', $file->getFile());
        self::assertCount(4, $file->getThumbnail());

        $this->trackForCleanup($file);

        self::assertFileExists($this->uploadDir.substr($file->getSource(), \strlen('/upload')));
        self::assertFileExists($this->uploadDir.substr($file->getFile(), \strlen('/upload')));

        foreach ($file->getThumbnail() as $index => $thumbnailPath) {
            self::assertStringStartsWith('/upload/img/thumbnail/', $thumbnailPath);
            $absolutePath = $this->uploadDir.substr($thumbnailPath, \strlen('/upload'));
            self::assertFileExists($absolutePath);

            [$thumbnailWidth] = getimagesize($absolutePath);
            self::assertSame(min(ImageProcessor::THUMBNAIL_WIDTHS[$index], 600), $thumbnailWidth);
        }
    }

    public function testUploadingASmallImageNeverUpscalesThumbnails(): void
    {
        $file = $this->service->upload($this->makeImageUpload(100, 80));
        $this->trackForCleanup($file);

        foreach ($file->getThumbnail() as $thumbnailPath) {
            $absolutePath = $this->uploadDir.substr($thumbnailPath, \strlen('/upload'));
            [$width] = getimagesize($absolutePath);
            self::assertLessThanOrEqual(100, $width);
        }
    }

    public function testUploadingAPdfStoresItWithoutImageMetadata(): void
    {
        $file = $this->service->upload($this->makePdfUpload(), 'A test document');

        self::assertSame(FileType::Pdf, $file->getType());
        self::assertNull($file->getWidth());
        self::assertNull($file->getSource());
        self::assertSame([], $file->getThumbnail());
        self::assertStringStartsWith('/upload/pdf/', $file->getFile());
        self::assertStringEndsWith('.pdf', $file->getFile());

        $this->trackForCleanup($file);
        self::assertFileExists($this->uploadDir.substr($file->getFile(), \strlen('/upload')));
    }

    public function testUploadingAnUnsupportedFileTypeIsRejected(): void
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_test_');
        file_put_contents($tmpPath, 'plain text content');
        $this->writtenPaths[] = $tmpPath;

        $this->expectException(\InvalidArgumentException::class);

        $this->service->upload(new UploadedFile($tmpPath, 'notes.txt', 'text/plain', null, true));
    }

    public function testResolveUrlFallsBackToPlaceholderWhenFileIsMissing(): void
    {
        self::assertSame(FileUploadService::PLACEHOLDER_URL, $this->service->resolveUrl(null));
        self::assertSame(FileUploadService::PLACEHOLDER_URL, $this->service->resolveUrl('/upload/img/webp/does-not-exist.webp'));

        $file = $this->service->upload($this->makeImageUpload(300, 300));
        $this->trackForCleanup($file);

        self::assertSame($file->getFile(), $this->service->resolveUrl($file->getFile()));
    }

    private function makeImageUpload(int $width, int $height): UploadedFile
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_test_').'.png';
        $this->writtenPaths[] = $tmpPath;

        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 120, 160, 200));
        imagepng($image, $tmpPath);
        imagedestroy($image);

        return new UploadedFile($tmpPath, 'sample.png', 'image/png', null, true);
    }

    private function makePdfUpload(): UploadedFile
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'upload_test_').'.pdf';
        $this->writtenPaths[] = $tmpPath;

        file_put_contents($tmpPath, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF");

        return new UploadedFile($tmpPath, 'document.pdf', 'application/pdf', null, true);
    }

    /** Registers a persisted File's on-disk paths for deletion in tearDown(). */
    private function trackForCleanup(File $file): void
    {
        foreach (array_filter([$file->getSource(), $file->getFile(), ...$file->getThumbnail()]) as $publicPath) {
            $this->writtenPaths[] = $this->uploadDir.substr($publicPath, \strlen('/upload'));
        }
    }
}

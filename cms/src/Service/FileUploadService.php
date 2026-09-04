<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\FileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Centralized entry point for storing an uploaded file (image, PDF or ZIP)
 * on disk under upload/ and persisting its App\Entity\File record. Any
 * plugin needing file/image handling should go through this service rather
 * than reinventing its own storage (see CLAUDE.md "Gestion des fichiers").
 */
class FileUploadService
{
    /** Public path served when a File's underlying disk file is missing (see resolveUrl()). */
    public const string PLACEHOLDER_URL = '/build/images/placeholder.svg';

    private const array IMAGE_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(
        private readonly string $uploadDir,
        private readonly ImageProcessor $imageProcessor,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Detects the upload's type, stores it under the matching upload/
     * subtree (running images through the webp + thumbnail pipeline), and
     * persists the resulting File entity.
     *
     * @throws \InvalidArgumentException if the upload's mime type is not an image/pdf/zip
     */
    public function upload(UploadedFile $uploadedFile, ?string $name = null, ?string $description = null): File
    {
        $type = $this->detectType($uploadedFile);
        $slugger = new AsciiSlugger();
        $slug = $slugger->slug(pathinfo($uploadedFile->getClientOriginalName(), \PATHINFO_FILENAME))->lower()->toString();
        $uniqid = uniqid();
        $datePrefix = (new \DateTimeImmutable())->format('Y-m-d_H-i-s');
        $originalSize = $uploadedFile->getSize();

        $file = new File();
        $file->setName($name ?? $uploadedFile->getClientOriginalName());
        $file->setDescription($description);
        $file->setUniqid($uniqid);
        $file->setSlug($slug);
        $file->setSize($originalSize);
        $file->setType($type);

        if (FileType::Image === $type) {
            $this->storeImage($uploadedFile, $file, $slug, $uniqid, $datePrefix);
        } else {
            $this->storeDocument($uploadedFile, $file, $type, $slug, $uniqid, $datePrefix);
        }

        $this->entityManager->persist($file);
        $this->entityManager->flush();

        return $file;
    }

    /**
     * Resolves a stored public path to a URL, falling back to the default
     * placeholder image when the underlying disk file no longer exists.
     */
    public function resolveUrl(?string $publicPath): string
    {
        if (null === $publicPath || !is_file($this->toAbsolutePath($publicPath))) {
            return self::PLACEHOLDER_URL;
        }

        return $publicPath;
    }

    /**
     * Moves the original image into upload/img/source/, then generates the
     * optimized webp and its thumbnails alongside it.
     */
    private function storeImage(UploadedFile $uploadedFile, File $file, string $slug, string $uniqid, string $datePrefix): void
    {
        $extension = strtolower($uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension());
        $sourceFilename = "{$datePrefix}_{$slug}.{$extension}";
        $sourceDir = $this->uploadDir.'/img/source';
        $this->ensureDirectory($sourceDir);
        $uploadedFile->move($sourceDir, $sourceFilename);
        $sourceAbsolutePath = $sourceDir.'/'.$sourceFilename;

        [$width, $height] = $this->imageProcessor->dimensions($sourceAbsolutePath);

        $webpFilename = "{$datePrefix}_{$uniqid}_{$slug}.webp";
        $webpDir = $this->uploadDir.'/img/webp';
        $this->imageProcessor->toWebp($sourceAbsolutePath, $webpDir.'/'.$webpFilename);

        $thumbnailDir = $this->uploadDir.'/img/thumbnail';
        $thumbnailFilenames = $this->imageProcessor->generateThumbnails(
            $sourceAbsolutePath,
            $thumbnailDir,
            fn (int $width) => "{$width}_{$datePrefix}_{$uniqid}_{$slug}.webp",
        );

        $file->setSource('/upload/img/source/'.$sourceFilename);
        $file->setFile('/upload/img/webp/'.$webpFilename);
        $file->setWidth($width);
        $file->setHeight($height);
        $file->setThumbnail(array_map(
            static fn (string $filename) => '/upload/img/thumbnail/'.$filename,
            $thumbnailFilenames,
        ));
    }

    /** Moves a PDF or ZIP upload directly into its upload/{pdf,zip}/ subtree. */
    private function storeDocument(UploadedFile $uploadedFile, File $file, FileType $type, string $slug, string $uniqid, string $datePrefix): void
    {
        $extension = $type->value;
        $filename = "{$datePrefix}_{$uniqid}_{$slug}.{$extension}";
        $dir = $this->uploadDir.'/'.$extension;
        $this->ensureDirectory($dir);
        $uploadedFile->move($dir, $filename);

        $file->setFile("/upload/{$extension}/{$filename}");
    }

    private function detectType(UploadedFile $uploadedFile): FileType
    {
        $mimeType = $uploadedFile->getMimeType();

        if (\in_array($mimeType, self::IMAGE_MIME_TYPES, true)) {
            return FileType::Image;
        }

        if ('application/pdf' === $mimeType) {
            return FileType::Pdf;
        }

        if (\in_array($mimeType, ['application/zip', 'application/x-zip-compressed'], true)) {
            return FileType::Zip;
        }

        throw new \InvalidArgumentException(\sprintf('Unsupported file type "%s".', $mimeType));
    }

    /** Maps a stored public path (e.g. "/upload/img/webp/x.webp") back to its absolute filesystem path. */
    private function toAbsolutePath(string $publicPath): string
    {
        return \dirname($this->uploadDir).'/'.ltrim($publicPath, '/');
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException(\sprintf('Unable to create directory "%s".', $directory));
        }
    }
}

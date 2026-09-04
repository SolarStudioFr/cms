<?php

namespace App\Service;

/**
 * GD-based image conversion: turns an uploaded source image into an
 * optimized webp copy plus a fixed set of webp thumbnails, used by
 * FileUploadService at upload time and reused as-is by the future
 * re-optimize action (step 05).
 */
class ImageProcessor
{
    /** Thumbnail widths, in pixels, generated for every image (never upscaled past the source width). */
    public const array THUMBNAIL_WIDTHS = [256, 512, 1024, 2048];

    private const int WEBP_QUALITY = 82;

    /**
     * Reads an image file's pixel dimensions.
     *
     * @return array{0: int, 1: int} width, height
     */
    public function dimensions(string $sourcePath): array
    {
        $size = getimagesize($sourcePath);

        if (false === $size) {
            throw new \RuntimeException(\sprintf('Unable to read image dimensions for "%s".', $sourcePath));
        }

        return [$size[0], $size[1]];
    }

    /**
     * Re-encodes a source image (jpeg/png/gif/webp) as an optimized webp file.
     *
     * @param string $sourcePath absolute filesystem path to the source image
     * @param string $destPath   absolute filesystem path to write the webp copy to
     */
    public function toWebp(string $sourcePath, string $destPath): void
    {
        $image = $this->load($sourcePath);
        $this->ensureDirectory(\dirname($destPath));

        try {
            if (!imagewebp($image, $destPath, self::WEBP_QUALITY)) {
                throw new \RuntimeException(\sprintf('Failed to write webp file to "%s".', $destPath));
            }
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * Generates one resized webp copy per entry in THUMBNAIL_WIDTHS, capping
     * the resize width at the source's own width to avoid upscaling.
     *
     * @param string $sourcePath      absolute filesystem path to the source image
     * @param string $destDir         absolute filesystem directory to write thumbnails into
     * @param string $destFilenameFor callback mapping a target width to the destination filename
     *
     * @return list<string> the filenames written, in the same order as THUMBNAIL_WIDTHS
     */
    public function generateThumbnails(string $sourcePath, string $destDir, callable $destFilenameFor): array
    {
        [$sourceWidth, $sourceHeight] = $this->dimensions($sourcePath);
        $image = $this->load($sourcePath);
        $this->ensureDirectory($destDir);

        $filenames = [];

        try {
            foreach (self::THUMBNAIL_WIDTHS as $targetWidth) {
                $width = min($targetWidth, $sourceWidth);
                $height = (int) round($sourceHeight * ($width / $sourceWidth));

                $resized = imagescale($image, $width, $height);
                if (false === $resized) {
                    throw new \RuntimeException(\sprintf('Failed to resize image to width %d.', $width));
                }

                $filename = $destFilenameFor($targetWidth);
                $destPath = $destDir.'/'.$filename;

                try {
                    if (!imagewebp($resized, $destPath, self::WEBP_QUALITY)) {
                        throw new \RuntimeException(\sprintf('Failed to write thumbnail to "%s".', $destPath));
                    }
                } finally {
                    imagedestroy($resized);
                }

                $filenames[] = $filename;
            }
        } finally {
            imagedestroy($image);
        }

        return $filenames;
    }

    /**
     * Loads any GD-supported source format into a GdImage resource/handle.
     *
     * @return \GdImage
     */
    private function load(string $sourcePath): \GdImage
    {
        // getimagesize()[2] (an IMAGETYPE_* constant) avoids depending on the
        // separate exif extension just to detect the format.
        $type = (getimagesize($sourcePath) ?: [])[2] ?? null;

        $image = match ($type) {
            \IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            \IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            \IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            \IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            default => throw new \RuntimeException(\sprintf('Unsupported image type for "%s".', $sourcePath)),
        };

        if (false === $image) {
            throw new \RuntimeException(\sprintf('Unable to load image "%s".', $sourcePath));
        }

        // Preserve transparency for png/gif sources instead of flattening to black.
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new \RuntimeException(\sprintf('Unable to create directory "%s".', $directory));
        }

        // See FileUploadService::ensureDirectory() for why this only runs for
        // directories we just created, forcing them to 0777.
        chmod($directory, 0777);
    }
}

<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FileRepository;

/**
 * Generic entity for every uploaded file (image, PDF or ZIP), backing the
 * centralized storage service any plugin can reuse instead of rolling its
 * own upload handling (see cms/src/Service/FileUploadService.php).
 */
#[ORM\Entity(repositoryClass: FileRepository::class)]
#[ORM\Table(name: 'file')]
#[ORM\HasLifecycleCallbacks]
class File
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Used as the alt attribute when the file is an image. */
    #[ORM\Column(length: 255)]
    private string $name = '';

    /** Optional internal documentation for the file, not shown publicly. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 32, unique: true)]
    private string $uniqid = '';

    #[ORM\Column(length: 255)]
    private string $slug = '';

    /** File size in bytes. */
    #[ORM\Column]
    private int $size = 0;

    /** Pixel width, only set for images. */
    #[ORM\Column(nullable: true)]
    private ?int $width = null;

    /** Pixel height, only set for images. */
    #[ORM\Column(nullable: true)]
    private ?int $height = null;

    /** Path (from the web root) to the untouched original, only set for images. */
    #[ORM\Column(length: 512, nullable: true)]
    private ?string $source = null;

    /** Path (from the web root) to the main deliverable: the optimized webp for images, or the pdf/zip itself. */
    #[ORM\Column(length: 512)]
    private string $file = '';

    /** Paths (from the web root) to the generated thumbnails, only set for images. */
    #[ORM\Column(type: Types::JSON)]
    private array $thumbnail = [];

    #[ORM\Column(length: 10, enumType: FileType::class)]
    private FileType $type;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $modifiedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->modifiedAt = new \DateTimeImmutable();
    }

    /** Bumps modifiedAt on every update, including re-optimization (step 05). */
    #[ORM\PreUpdate]
    public function touchModifiedAt(): void
    {
        $this->modifiedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUniqid(): string
    {
        return $this->uniqid;
    }

    public function setUniqid(string $uniqid): static
    {
        $this->uniqid = $uniqid;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): static
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): static
    {
        $this->file = $file;

        return $this;
    }

    /** @return list<string> */
    public function getThumbnail(): array
    {
        return $this->thumbnail;
    }

    /** @param list<string> $thumbnail */
    public function setThumbnail(array $thumbnail): static
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    public function getType(): FileType
    {
        return $this->type;
    }

    public function setType(FileType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getModifiedAt(): \DateTimeImmutable
    {
        return $this->modifiedAt;
    }
}

<?php

declare(strict_types=1);

namespace App\Api\Post\Entity;

use App\Api\Post\Entity\Enum\PostStatus;
use App\Api\Post\Repository\V1\PostRepository;
use App\Api\User\Entity\User;
use App\Entity\AbstractEntity;
use App\Entity\Trait\HasUlid;
use App\Entity\Trait\Sluggable;
use App\Entity\Trait\Timestampable;
use Carbon\CarbonImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Post extends AbstractEntity
{
    use HasUlid;
    use Sluggable;
    use Timestampable;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, enumType: PostStatus::class)]
    #[Groups(['v1_metadata'])]
    private PostStatus $status = PostStatus::DRAFT;

    #[ORM\Column(length: 255)]
    #[NotBlank]
    #[Length(min: 5, max: 255)]
    #[Groups(['v1_post'])]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[NotBlank]
    #[Length(min: 300, max: 65535)]
    #[Groups(['v1_post'])]
    private ?string $body = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\Column(name: 'published_at', nullable: true)]
    #[Groups(['v1_metadata'])]
    private ?\DateTimeImmutable $publishedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function getStatus(): PostStatus
    {
        return $this->status;
    }

    public function setStatus(PostStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getPublishedAt(): ?CarbonImmutable
    {
        if ($this->publishedAt === null) {
            return null;
        }

        return CarbonImmutable::create($this->publishedAt);
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return string[]
     */
    public function sluggableFields(): array
    {
        return ['title'];
    }
}

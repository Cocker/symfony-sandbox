<?php

namespace App\Api\Post\Entity;

use App\Api\Post\Entity\Enum\PostCommentStatus;
use App\Api\Post\Repository\V1\PostCommentRepository;
use App\Api\User\Entity\User;
use App\Entity\AbstractEntity;
use App\Entity\Trait\HasUlid;
use App\Entity\Trait\Timestampable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

#[ORM\Entity(repositoryClass: PostCommentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class PostComment extends AbstractEntity
{
    use HasUlid;
    use Timestampable;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'postComments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    #[ORM\Column(type: Types::STRING, enumType: PostCommentStatus::class)]
    private PostCommentStatus $status;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['v1_comment'])]
    #[NotBlank]
    #[Length(max: 300)]
    private ?string $content = null;

    public function __construct()
    {
        $this->status = PostCommentStatus::PENDING;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPost(): ?Post
    {
        return $this->post;
    }

    public function setPost(?Post $post): static
    {
        $this->post = $post;

        return $this;
    }

    public function getStatus(): PostCommentStatus
    {
        return $this->status;
    }

    public function setStatus(PostCommentStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }
}

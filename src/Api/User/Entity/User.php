<?php

declare(strict_types=1);

namespace App\Api\User\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Api\Post\Entity\Post;
use App\Api\Post\Entity\PostComment;
use App\Api\User\Entity\Enum\UserRole;
use App\Api\User\Entity\Enum\UserStatus;
use App\Api\User\Repository\V1\UserRepository;
use App\Entity\AbstractEntity;
use App\Entity\Trait\HasUlid;
use App\Entity\Trait\Timestampable;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\Type;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('email', 'User with this email already exists.')]
#[ApiResource]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface
{
    use HasUlid;
    use Timestampable;

    public final const int MAX_EMAIL_LENGTH = 180;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, enumType: UserStatus::class)]
    #[Groups(['v1_metadata'])]
    private UserStatus $status;

    #[ORM\Column(type: Types::STRING, length: self::MAX_EMAIL_LENGTH, unique: true)]
    #[NotBlank]
    #[Email]
    #[Length(max: self::MAX_EMAIL_LENGTH)]
    #[Groups(['v1_personal'])]
    private string $email;

    private ?string $newEmail = null;

    #[ORM\Column(name: 'first_name', type: Types::STRING)]
    #[NotBlank]
    #[Type('string')]
    #[Length(min: 3, max: 255)]
    #[Groups(['v1_personal'])]
    private string $firstName;

    #[ORM\Column(name: 'last_name', type: Types::STRING)]
    #[NotBlank]
    #[Type('string')]
    #[Length(min: 3, max: 255)]
    #[Groups(['v1_personal'])]
    private string $lastName;

    /**
     * @var string[]
     */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['v1_metadata'])]
    private array $roles;

    #[ORM\Column(type: Types::STRING)]
    #[Ignore]
    private ?string $password;

    #[ORM\Column(name: 'email_verified_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['v1_metadata'])]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    #[NotBlank(['groups' => ['password']])]
    #[Type('string')]
    #[Length(min: 8, max: 255)]
    #[PasswordStrength(minScore: PasswordStrength::STRENGTH_MEDIUM)]
    private ?string $plainPassword = null;

    /**
     * @var Collection<int, UserLogin> $logins
     */
    #[ORM\OneToMany(targetEntity: UserLogin::class, mappedBy: 'causer', orphanRemoval: true)]
    private Collection $logins;

    /**
     * @var Collection<int, Post> $posts
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $posts;

    /**
     * @var Collection<int, PostComment> $postComments
     */
    #[ORM\OneToMany(targetEntity: PostComment::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $postComments;

    public function __construct()
    {
        $this->status = UserStatus::UNVERIFIED;
        $this->roles = [UserRole::USER->value];
        $this->logins = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->postComments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(#[\SensitiveParameter] string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(#[\SensitiveParameter] ?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        $this->password = null;

        return $this;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function setStatus(UserStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @param string[] $roles
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getEmailVerifiedAt(): ?\DateTimeImmutable
    {
        if ($this->emailVerifiedAt === null) {
            return null;
        }

        return CarbonImmutable::create($this->emailVerifiedAt);
    }

    public function setEmailVerifiedAt(?\DateTimeImmutable $emailVerifiedAt): void
    {
        $this->emailVerifiedAt = $emailVerifiedAt;
    }


    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    /**
     * @return Collection<int, UserLogin>
     */
    public function getLogins(): Collection
    {
        return $this->logins;
    }

    public function addLogin(UserLogin $login): static
    {
        if (!$this->logins->contains($login)) {
            $this->logins->add($login);
            $login->setCauser($this);
        }

        return $this;
    }

    public function removeLogin(UserLogin $login): static
    {
        // set the owning side to null (unless already changed)
        if ($this->logins->removeElement($login) && $login->getCauser() === $this) {
            $login->setCauser(null);
        }

        return $this;
    }

    public function getNewEmail(): ?string
    {
        return $this->newEmail;
    }

    public function setNewEmail(?string $newEmail = null): void
    {
        $this->newEmail = $newEmail;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostComment>
     */
    public function getPostComments(): Collection
    {
        return $this->postComments;
    }

    public function addPostComment(PostComment $postComment): static
    {
        if (!$this->postComments->contains($postComment)) {
            $this->postComments->add($postComment);
            $postComment->setAuthor($this);
        }

        return $this;
    }

    public function removePostComment(PostComment $postComment): static
    {
        if ($this->postComments->removeElement($postComment)) {
            // set the owning side to null (unless already changed)
            if ($postComment->getAuthor() === $this) {
                $postComment->setAuthor(null);
            }
        }

        return $this;
    }
}

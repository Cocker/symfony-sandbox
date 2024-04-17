<?php

declare(strict_types=1);

namespace App\Api\Post\Voter;

use App\Api\Post\Entity\Post;
use App\Api\Post\Voter\Enum\PostVoterAttribute;
use App\Api\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PostVoter extends Voter
{
    public final const string GET = 'post.get';
    public final const string GET_ANY = 'post.get_any';
    public final const string COMPLETE = 'post.complete';
    public final const string UPDATE = 'post.update';
    public final const string PUBLISH = 'post.publish';
    public final const string REJECT = 'post.reject';

    public function __construct(protected readonly Security $security)
    {
        //
    }

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [
            self::GET,
            self::COMPLETE,
            self::UPDATE,
            self::PUBLISH,
            self::REJECT,
            self::GET_ANY,
        ], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === Post::class || $subjectType === 'string';
    }


    protected function supports(string $attribute, mixed $subject): bool
    {
        if (! $this->supportsAttribute($attribute)) {
            return false;
        }

        if (\is_object($subject) && ! $this->supportsType($subject::class)) {
            return false;
        }

        if (\is_string($subject) && ! $this->supportsType($subject)) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (! $user instanceof User) {
            return false;
        }

        /** @var Post $post */
        $post = $subject;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return match ($attribute) {
            self::GET, self::UPDATE, self::COMPLETE => $post->getAuthor() === $user,
            self::PUBLISH, self::REJECT, self::GET_ANY => false, // only admin
        };
    }
}

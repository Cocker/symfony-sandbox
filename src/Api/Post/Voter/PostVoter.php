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
    public final const string VIEW = 'view';
    public final const string COMPLETE = 'complete';
    public final const string UPDATE = 'update';
    public final const string PUBLISH = 'publish';
    public final const string REJECT = 'reject';

    public function __construct(protected readonly Security $security)
    {
        //
    }

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [
            self::VIEW,
            self::COMPLETE,
            self::UPDATE,
            self::PUBLISH,
            self::REJECT,
        ], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === Post::class;
    }


    protected function supports(string $attribute, mixed $subject): bool
    {
        if (! $this->supportsAttribute($attribute)) {
            return false;
        }

        if (! is_object($subject) || ! $this->supportsType($subject::class)) {
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
            self::VIEW, self::UPDATE, self::COMPLETE => $post->getAuthor() === $user,
            self::PUBLISH, self::REJECT => false, // only admin can publish/reject
        };
    }
}

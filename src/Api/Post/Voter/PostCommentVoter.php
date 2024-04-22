<?php

declare(strict_types=1);

namespace App\Api\Post\Voter;

use App\Api\Post\Entity\PostComment;
use App\Api\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, PostComment>
 */
class PostCommentVoter extends Voter
{
    public const APPROVE = 'post-comment.approve';
    public const REJECT = 'post-comment.reject';

    public function __construct(protected readonly Security $security)
    {
        //
    }

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [
            self::APPROVE,
            self::REJECT,
        ], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === PostComment::class;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (! $this->supportsAttribute($attribute)) {
            return false;
        }

        if (! $this->supportsType($subject::class)) {
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

        return $this->security->isGranted('ROLE_ADMIN');
    }
}

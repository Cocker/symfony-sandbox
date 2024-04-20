<?php

declare(strict_types=1);

namespace App\Api\User\Voter;

use App\Api\User\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, User>
 */
class UserVoter extends Voter
{
    public final const string VIEW = 'user.view';
    public final const string UPDATE = 'user.update';

    public function __construct(private readonly Security $security)
    {
        //
    }

    public function supportsAttribute(string $attribute): bool
    {
        return in_array($attribute, [self::VIEW, self::UPDATE], true);
    }

    public function supportsType(string $subjectType): bool
    {
        return $subjectType === User::class;
    }

    public function supports(string $attribute, mixed $subject): bool
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
        $authenticatedUser = $token->getUser();

        if (!$authenticatedUser instanceof User) {
            return false;
        }

        /** @var User $user */
        $user = $subject;

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return match ($attribute) {
            self::VIEW => $this->canView($user, $authenticatedUser),
            self::UPDATE => $this->canUpdate($user, $authenticatedUser),
            default => throw new \LogicException("Unexpected attribute: $attribute"),
        };
    }

    private function canUpdate(User $user, User $authenticatedUser): bool
    {
        return $user->getId() === $authenticatedUser->getId();
    }

    private function canView(User $user, User $authenticatedUser): bool
    {
        return $this->canUpdate($user, $authenticatedUser);
    }
}

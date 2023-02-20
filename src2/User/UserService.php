<?php

declare(strict_types=1);

namespace App\User;

use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\User\CurrentUser;

final class UserService
{
    public function __construct(
        private CurrentUser $currentUser,
        private UserRepository $repository,
        private AccessCheckerInterface $accessChecker
    ) {
    }
    
    public function getUser(): object|null
    {
        $userId = $this->currentUser->getId();
        if (null!==$userId) {
           return $this->repository->findById($userId); 
        } else {
           return null;
        } 
    }

    public function hasPermission(string $permission): bool
    {
        $userId = $this->currentUser->getId();

        return null !== $userId && $this->accessChecker->userHasPermission($userId, $permission);
    }
}

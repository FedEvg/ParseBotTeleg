<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository         $userRepository,
    )
    {
    }

    public function createUser(string $username, int $userId): User
    {
        $existingUser = $this->userRepository->findOneBy(['userId' => $userId]);

        if ($existingUser) {
            return $existingUser;
        }

        $user = (new User())
            ->setUsername($username)
            ->setUserId($userId)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }


}
<?php

namespace App\Service;

use App\Entity\Channel;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class TagService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TagRepository          $tagRepository,
    )
    {
    }

    public function createTag(
        string  $name,
        Channel $channel = null,
    ): Tag
    {
        $existingTag = $this->findByTag($name);

        if ($existingTag) {
            return $existingTag;
        }

        $newTag = (new Tag())
            ->setName($name)
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        if ($channel) {
            $channel->addTag($newTag);
            $this->entityManager->persist($channel);
        }

        $this->entityManager->persist($newTag);
        $this->entityManager->flush();

        return $newTag;
    }

    public function findByName(string $name): ?Tag
    {
        return $this->tagRepository->findOneBy(['name' => $name]);
    }

    /**
     * @throws \Exception
     */
    public function deleteChannelByTag(string $tag): void
    {
        $channel = $this->tagRepository->findOneBy(['channel_tag' => $tag]);

        if (!$channel) {
            throw new \Exception('Tag не знайдений.');
        }

        $this->entityManager->remove($channel);
        $this->entityManager->flush();
    }
}

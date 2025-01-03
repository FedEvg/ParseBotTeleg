<?php

namespace App\Service;

use App\Entity\Channel;
use App\Entity\User;
use App\Repository\ChannelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

readonly class ChannelService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ChannelRepository      $channelRepository,
    )
    {
    }

    public function createChannel(
        object $channel,
        bool   $isOwn = false,
        User   $user = null,
    ): Channel
    {
        $existingChannel = $this->findByTag($channel['username']);

        if ($existingChannel) {
            return $existingChannel;
        }

        $newChannel = (new Channel())
            ->setChannelTag('@' . $channel['username'])
            ->setName($channel['title'])
            ->setIsOwn($isOwn)
            ->setChannelId($channel['id'])
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime());

        if ($user) {
            $user->addChannel($newChannel);
            $this->entityManager->persist($user);
        }

        $this->entityManager->persist($newChannel);
        $this->entityManager->flush();

        return $newChannel;
    }

    public function findByTag(string $tag): ?Channel
    {
        return $this->channelRepository->findOneBy(['channel_tag' => $tag]);
    }

    /**
     * @throws Exception
     */
    public function getChannel(bool $isOwn = false): array
    {
        $channels = $this->channelRepository->findByIsOwn($isOwn);

        if (empty($channels)) {
            throw new Exception('Немає доступних каналів.');
        }

        return $channels;
    }

    /**
     * @throws Exception
     */
    public function getChannelListForTelegram(bool $isOwn = false): array
    {
        $channels = $this->getChannel($isOwn);

        $data = [];
        foreach ($channels as $index => $channel) {
            $data[] = ($index + 1) . '. ' . $channel->getChannelTag();
        }

        return $data;
    }

    /**
     * @throws Exception
     */
    public function deleteChannelByTag(string $tag): void
    {
        $channel = $this->findByTag($tag);

        if (!$channel) {
            throw new Exception('Канал не знайдений.');
        }

        $this->entityManager->remove($channel);
        $this->entityManager->flush();
    }
}

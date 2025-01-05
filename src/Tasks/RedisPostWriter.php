<?php

namespace App\Tasks;

use App\Service\ChannelService;
use App\Service\ParsePostService;
use App\Service\RedisService;
use Exception;
use Psr\Log\LoggerInterface;

readonly class RedisPostWriter
{
    public function __construct(
        private ChannelService   $channelService,
        private ParsePostService $parsePostService,
        private RedisService     $redis,
        private LoggerInterface  $logger,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function savePostToRedis(): void
    {
        $this->logger->info('Starting to save posts to Redis.');

        $channels = $this->channelService->getChannel();

        foreach ($channels as $channel) {
            $this->logger->info("Processing channel: {$channel->getChannelTag()}");

            $posts = $this->parsePostService->getLastPost($channel->getChannelTag(), 10);

            foreach ($posts as $post) {
                if ($this->isAdPost($post['message'])) {
                    $this->logger->info("Skipping ad post: {$post['message']}");
                    continue;
                }

                $this->savePost($post);
            }
        }

        $this->logger->info('Finished saving posts to Redis.');
    }

    private function isAdPost(string $message): bool
    {
        $adKeywords = $this->getAdKeywords();
        $isAd = $this->containsAdKeyword($message, $adKeywords);

        $this->logger->debug("Checking if post is ad: {$message} -> " . ($isAd ? 'YES' : 'NO'));

        return $isAd;
    }

    private function getAdKeywords(): array
    {
        return [
            'скидка', 'акция', 'подарок', 'розыгрыш', 'приз',
            'звоните', 'доставка', 'продажа', 'купи', 'забронируйте',
            'ресторан', 'живая музыка', 'бронируй', 'по адресу'
        ];
    }

    private function containsAdKeyword(string $message, array $keywords): bool
    {
        $message = mb_strtolower($message);
        return (bool) array_filter($keywords, fn($keyword) => stripos($message, mb_strtolower($keyword)) !== false);
    }

    /**
     * @throws Exception
     */
    private function savePost(array $post): void
    {
        $key = $this->generateRedisKey($post);

        if (!$this->redis->exists($key)) {
            try {
                $this->logger->info("Saving post to Redis with key: {$key}");
                $this->redis->setHash($key, (array)json_encode($post), 43200);
            } catch (Exception $e) {
                $this->logger->error("Failed to save post to Redis: {$e->getMessage()}");
                throw $e;
            }
        } else {
            $this->logger->info("Post already exists in Redis: {$key}");
        }
    }

    private function generateRedisKey(array $post): string
    {
        $key = "{$post['channel']}_{$post['postId']}_{$post['groupId']}";
        $this->logger->debug("Generated Redis key: {$key}");
        return $key;
    }
}

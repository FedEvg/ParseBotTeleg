<?php

namespace App\Tasks;

use App\Service\ChannelService;
use App\Service\ParsePostService;
use App\Service\RedisService;
use Exception;

readonly class RedisPostWriter
{
    public function __construct(
        private ChannelService   $channelService,
        private ParsePostService $parsePostService,
        private RedisService     $redis,
    )
    {

    }

    /**
     * @throws Exception
     */
    public function savePostToRedis(): void
    {
        $channels = $this->channelService->getChannel();

        foreach ($channels as $channel) {
            $posts = $this->parsePostService->getLastPost($channel->getChannelTag(), 10);

            foreach ($posts as $post) {
                if ($this->isAdPost($post['message'])) {
                    continue;
                }

                $this->savePost($post);
            }
        }
    }

    private function isAdPost(string $message): bool
    {
        $adKeywords = $this->getAdKeywords();

        return $this->containsAdKeyword($message, $adKeywords);
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
        return (bool) array_filter($keywords, fn($keyword) => stripos($message, mb_strtolower($keyword)) !== false); // Преобразуем ключевые слова в нижний регистр
    }


    /**
     * @throws Exception
     */
    private function savePost(array $post): void
    {
        $key = $this->generateRedisKey($post);

        if (!$this->redis->exists($key)) {
            try {
                $this->redis->setHash($key, (array)json_encode($post), 43200);
            } catch (Exception $e) {
                dd($e->getMessage());
            }
        }
    }

    private function generateRedisKey(array $post): string
    {
        return "{$post['channel']}_{$post['postId']}_{$post['groupId']}";
    }


}
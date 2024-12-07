<?php

namespace App\Service;

use danog\MadelineProto\API;

readonly class ParseNewsService
{
    public function __construct(
        private API $madelineProto
    )
    {
    }

    /**
     * @throws \Exception
     */
    public function getLastNews(string $channelName, int $limit = 1): ?array
    {
        try {
            $channel = '@' . $channelName;
            $posts = $this->madelineProto->messages->getHistory([
                'peer' => $channel,
                'limit' => $limit,
            ]);

            if (isset($posts['messages']) && count($posts['messages']) > 0) {
                $groupedMessages = [];

                foreach ($posts['messages'] as $post) {
                    if (isset($post['grouped_id'])) {
                        $groupedMessages[$post['grouped_id']][] = $post;
                    } else {
                        $groupedMessages[$post['id']] = [$post];
                    }
                }

                $news = [];
                foreach ($groupedMessages as $groupedId => $group) {
                    foreach ($group as $post) {
                        if (isset($post['message'])) {
                            $news[] = [
                                'message' => $post['message'],
                                'url' => "https://t.me/$channelName/{$post['id']}"
                            ];
                        }
                    }
                }

                return $news;
            }

            return null;
        } catch (\Throwable $e) {
            throw new \Exception('Error fetching news: ' . $e->getMessage());
        }
    }
}
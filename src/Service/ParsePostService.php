<?php

namespace App\Service;

use danog\MadelineProto\API;
use DateTime;
use DateTimeZone;
use Exception;

readonly class ParsePostService
{
    public function __construct(
        private API $madelineProto
    )
    {
    }

    /**
     * @throws \DateMalformedStringException
     * @throws Exception
     */
    public function getLastPost(string $channelName, int $limit = 1): ?array
    {
        $now = (new DateTime('now', new DateTimeZone('UTC')))->getTimestamp();
        $twoHoursAgo = $now - (2 * 3600);

        try {
            $posts = $this->madelineProto->messages->getHistory([
                'peer' => $channelName,
                'offset_date' => $now,
                'limit' => $limit,
            ]);

            $grouped = [];
            foreach ($posts['messages'] as $post) {
                if (isset($post['date']) && $post['date'] >= $twoHoursAgo) {
                    $groupedId = $post['grouped_id'] ?? $post['id'];
                    $grouped[$groupedId][] = $post;
                }
            }

            $data = [];

            foreach ($grouped as $groupId => $posts) {
                $message = '';
                $minPostId = PHP_INT_MAX;
                $photoCount = 0;
                $videoCount = 0;
                $firstPostDate = PHP_INT_MAX;

                foreach ($posts as $post) {
                    if ($message === '') {
                        $message = $post['message'];
                    }

                    $minPostId = min($minPostId, $post['id']);
                    $photoCount += !empty($post['media']['photo']);
                    $videoCount += !empty($post['media']['video']);
                    $firstPostDate = min($firstPostDate, $post['date']);
                }

                $channelNameForUrl = ltrim($channelName, '@');

                $data[] = [
                    'message' => $message,
                    'groupId' => $groupId,
                    'postId' => $minPostId,
                    'photoCount' => $photoCount,
                    'videoCount' => $videoCount,
                    'date' => $firstPostDate,
                    'channel' => $channelName,
                    'url' => "https://t.me/$channelNameForUrl/$minPostId",
                ];
            }

            return $data;

        } catch (\Throwable $e) {
            throw new Exception('Error fetching news: ' . $e->getMessage());
        }
    }
}
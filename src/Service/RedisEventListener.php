<?php

namespace App\Service;

use App\Telegram\ParseBot;
use Exception;
use Telegram\Bot\Exceptions\TelegramSDKException;

readonly class RedisEventListener
{
    public function __construct(
        private RedisService $redisService,
        private ParseBot     $parseBot,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function listenToHashEvents(): void
    {
        try {
            $this->redisService->subscribeToChannels(['hash_written'], function ($message) {
                $data = json_decode($message->payload, true);
                if (isset($data['key'], $data['data'])) {
                    $this->handleHashWrittenEvent($data['key'], $data['data']);
                }
            });
        } catch (\Exception $e) {
            throw new Exception("Ошибка при подписке на канал Redis: " . $e->getMessage());
        }
    }

    /**
     * @throws TelegramSDKException
     * @throws Exception
     */
    private function handleHashWrittenEvent(string $key, array $data): void
    {
        if (isset($data[0])) {
            $decodedData = json_decode($data[0], true);

            if ($decodedData === null) {
                throw new Exception("Ошибка декодирования JSON данных для ключа: $key");
            }

            $message = $this->formatMessage($decodedData);
            $this->sendMessageToUser($message);
        }
    }

    private function formatMessage(array $decodedData): string
    {
        return sprintf(
            "Новый пост:\nСообщение:\n %s\nФото: %d\nВидео: %d\nСсылка: %s\nДата: %s\n",
            $decodedData['message'],
            $decodedData['photoCount'],
            $decodedData['videoCount'],
            $decodedData['url'],
            date('Y-m-d H:i:s', $decodedData['date'])
        );
    }

    /**
     * @throws TelegramSDKException
     */
    private function sendMessageToUser(string $message): void
    {
        $this->parseBot->sendMessage([
            'chat_id' => 6469279896,
            'text' => $message,
        ]);
    }
}

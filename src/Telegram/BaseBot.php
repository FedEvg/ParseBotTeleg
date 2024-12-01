<?php

namespace App\Telegram;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

abstract class BaseBot extends Api
{
    public function __construct(string $token)
    {
        parent::__construct($token);
    }

    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @throws TelegramSDKException
     */
    public function sendChatMessage(int $chatId, string $text, array $extraParams = []): void
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extraParams);

        $this->sendMessage($params);
    }
}

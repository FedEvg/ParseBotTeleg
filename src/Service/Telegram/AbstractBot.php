<?php

namespace App\Service\Telegram;

use AllowDynamicProperties;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

#[AllowDynamicProperties] abstract class AbstractBot extends Api
{
    public function __construct(
        protected readonly string        $token,
        private readonly string          $domain,
        private readonly LoggerInterface $logger,
    )
    {
        parent::__construct($token);
    }

    /**
     * @param $method
     * @param $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (method_exists($this, $method)) {
            return $this->$method(...$parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * @param int $chatId
     * @param string $text
     * @param array $extraParams
     * @return void
     * @throws TelegramSDKException
     */
    public function sendChatMessage(
        int   $chatId, string $text,
        array $extraParams = []
    ): void
    {
        $params = array_merge([
            'chat_id' => $chatId,
            'text' => $text,
        ], $extraParams);

        $this->sendMessage($params);
    }

    /**
     * @param string $url
     * @return void
     * @throws TelegramSDKException
     */
    public function setWebhookPath(string $url): void
    {
        if (empty($url)) {
            throw new InvalidArgumentException('Webhook URL not provided.');
        }

        try {
            $this->setWebhook([
                'url' => $this->domain . $url,
                'allowed_updates' => ['message', 'callback_query'],
            ]);
            $this->logger->info('Set webhook URL: ' . $this->domain . $url);
        } catch (TelegramSDKException $e) {
            throw new TelegramSDKException("Failed to set webhook: " . $e->getMessage());
        }
    }

    public function getType(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        if (str_ends_with($className, 'Bot')) {
            $className = substr($className, 0, -3);
        }
        return strtolower($className);
    }
}
<?php

namespace App\Command\Telegram\Channel;

use App\Command\Telegram\AbstractCommand;
use App\Entity\User;
use App\Service\ChannelService;
use Telegram\Bot\Api;

class ListMyChannel extends AbstractCommand
{
    protected string $name = 'Список ваших власних каналів.';
    protected string $command = '/list_my_channels';
    protected string $description = 'Ця команда показує список ваших каналів для публікації.';

    public function __construct(
        Api                             $bot,
        private readonly ChannelService $channelService,
    )
    {
        parent::__construct($bot);
    }

    public function handle($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];

        try {
            $channels = $this->channelService->getChannelListForTelegram(true);

            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "Ваш список каналів:\n" . implode("\n", $channels),
            ]);
        } catch (\Exception $e) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Виникла помилка при виведені каналів: ' . $e->getMessage(),
            ]);

            return;
        }
    }
}
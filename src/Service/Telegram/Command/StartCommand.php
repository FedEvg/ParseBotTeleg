<?php

namespace App\Service\Telegram\Command;

use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;
use App\Service\Telegram\AbstractBot;
use App\Service\Telegram\Command\Abstract\AbstractCommand;
use Telegram\Bot\Exceptions\TelegramSDKException;

class StartCommand extends AbstractCommand
{
    public function __construct(
        protected AbstractBot $bot
    )
    {
        parent::__construct($bot, '/start', 'Launching the bot and greeting the user.');
    }

    /**
     * @throws TelegramSDKException
     */
    public function executeCommand(WebhookDTO $webhook, ?User $user = null): void
    {
        $this->bot->sendChatMessage(
            $webhook->getChat()->getId(),
            'Welcome to the bot! Your username is @.' . $webhook->getChat()->getUserName()
        );
    }
}

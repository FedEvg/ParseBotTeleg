<?php

namespace App\Command\Telegram;

use Telegram\Bot\Api;
use App\Entity\User;

class StartCommand extends AbstractCommand
{
    protected string $name = 'Старт';
    protected string $command = '/start';
    protected string $description = 'Запуск бота.';

    public function __construct(
        Api $bot
    )
    {
        parent::__construct($bot);
    }

    public function handle($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];
        $username = $message['chat']['username'];

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Ласкаво просимо до бота! Ваш юзернейм @' . $username,
        ]);
    }
}

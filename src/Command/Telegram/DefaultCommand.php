<?php

namespace App\Command\Telegram;

use App\Entity\User;
use App\Telegram\ConfigBot;

class DefaultCommand extends AbstractCommand
{
    protected string $name = 'Default';
    protected string $command = '/default';
    protected string $description = 'Обробка інших повідомлень';

    public function __construct(
        ConfigBot $bot,
    )
    {
        parent::__construct($bot); // Передаем $bot в родительский конструктор
    }

    public function handle($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];
        $text = $message["text"];
        $username = $message["from"]["username"];

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Ви написали: ' . $text . '. Ваш юзернейм @' . $username,
        ]);
    }
}
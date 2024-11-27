<?php

namespace App\Command\Telegram;

use App\Entity\User;
use Telegram\Bot\Api;

class MenuCommand extends AbstractCommand
{
    protected string $name = 'Меню';
    protected string $command = '/menu';
    protected string $description = 'Виклик меню.';

    /**
     * @throws \Exception
     */
    public function __construct(
        Api $bot,
    )
    {
        parent::__construct($bot);
    }

    function getKeyboard(): array
    {
        return [
            [
                ['text' => 'Start', 'callback_data' => '/start'],
                ['text' => 'Кнопка 2', 'callback_data' => 'button2'],
            ],
            [
                ['text' => 'Кнопка 3', 'callback_data' => 'button3'],
            ],
        ];
    }

    public function handle($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];

        $this->bot->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Выберіть опцію:',
            'reply_markup' => json_encode([
                'inline_keyboard' => $this->getKeyboard(),
            ]),
        ]);
    }
}
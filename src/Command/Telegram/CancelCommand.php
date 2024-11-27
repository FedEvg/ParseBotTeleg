<?php

namespace App\Command\Telegram;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Telegram\Bot\Api;

class CancelCommand extends AbstractCommand
{
    protected string $name = 'Відмінити команду';
    protected string $command = '/cancel_command';
    protected string $description = 'Відмінити команду, щоб не давати відповідь.';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        Api                                     $bot
    )
    {
        parent::__construct($bot);
    }

    public function handle($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];

        if (!$user->getWaitingForMessage()) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Немає незавершених команд для скасування.',
            ]);

            return;
        }

        $user->setWaitingForMessage(null);

        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Команду скасовано успішно.',
            ]);
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Сталася помилка при скасуванні команди: ' . $e->getMessage(),
            ]);
        }
    }
}

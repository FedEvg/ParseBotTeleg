<?php

namespace App\Service\Telegram\Command;

use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;
use App\Service\Telegram\AbstractBot;
use App\Service\Telegram\Command\Abstract\AbstractCommand;
use Doctrine\ORM\EntityManagerInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;

class CancelCommand extends AbstractCommand
{
    public function __construct(
        protected AbstractBot                   $bot,
        private readonly EntityManagerInterface $entityManager,
    )
    {
        parent::__construct($bot, '/cancel', 'Launching the bot and greeting the user.');
    }

    /**
     * @throws TelegramSDKException
     */
    public function executeCommand(WebhookDTO $webhook, ?User $user = null): void
    {
        $chatId = $webhook->getChat()->getId();

        if (!$user->getWaitingForMessage()) {
            $this->bot->sendChatMessage($chatId, 'There are no unfinished commands to cancel.');

            return;
        }

        $user->setWaitingForMessage(null);

        try {
            $this->entityManager->beginTransaction();
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $this->entityManager->commit();

            $this->bot->sendChatMessage($chatId, 'The command was canceled successfully.');
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->bot->sendChatMessage($chatId, 'An error occurred while canceling the command: ' . $e->getMessage());
        }
    }
}

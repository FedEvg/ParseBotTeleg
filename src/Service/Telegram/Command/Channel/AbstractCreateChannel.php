<?php

namespace App\Service\Telegram\Command\Channel;

use App\DTO\Telegram\ChannelDTO;
use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;
use App\Service\ChannelService;
use App\Service\Telegram\AbstractBot;
use App\Service\Telegram\Command\Abstract\AbstractInteractiveCommand;
use App\Validator\ChannelValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

abstract class AbstractCreateChannel extends AbstractInteractiveCommand
{
    public function __construct(
        AbstractBot                               $bot,
        private readonly EntityManagerInterface   $entityManager,
        private readonly ChannelService           $channelService,
        private readonly ChannelValidationService $channelValidationService,
    )
    {
        parent::__construct($bot, '', '', '');
    }

    public function executeCommand(WebhookDTO $webhook, ?User $user = null): void
    {
        $chatId = $webhook->getChat()->getId();

        try {
            $user->setWaitingForMessage($this->getName());
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->bot->sendChatMessage($chatId, $this->getMessageToAction());
        } catch (Exception $e) {
            $this->bot->sendChatMessage($chatId, 'Error adding a channel for parsing: ' . $e->getMessage());

            return;
        }
    }
}
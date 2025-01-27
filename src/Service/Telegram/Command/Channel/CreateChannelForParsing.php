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

class CreateChannelForParsing extends AbstractInteractiveCommand
{
    public function __construct(
        AbstractBot                               $bot,
        private readonly EntityManagerInterface   $entityManager,
        private readonly ChannelService           $channelService,
        private readonly ChannelValidationService $channelValidationService,
    )
    {
        parent::__construct(
            $bot,
            '/add_parsing_channel',
            'This command allows you to add a channel for parsing. After entering the command, specify the tag of your channel.', // Уточненное описание
            'Please provide the channel tag (e.g., @telegram_channel_parser).'
        );
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

    public function executeInteractiveCommand(WebhookDTO $webhook, ?User $user = null): void
    {
        $chatId = $webhook->getChat()->getId();
        $tagChannel = $webhook->getText();

        try {
            $channel = $this->bot->getChannelInfo($tagChannel);
            $this->validateChannel($channel);

            $this->processChannel($channel, $user);
            $this->bot->sendChatMessage($chatId, "Channel $tagChannel has been successfully verified and included!");
        } catch (\Exception $e) {
            $this->bot->sendChatMessage($chatId, 'Error when adding a channel: ' . $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    private function validateChannel($channel): void
    {
        $validationErrors = $this->channelValidationService->validateChannel($channel);

        if (count($validationErrors) > 0) {
            throw new Exception(implode(",", $validationErrors));
        }
    }

    private function processChannel(ChannelDTO $channel, ?User $user): void
    {
        $this->channelService->createChannel($channel, false, $user);

        if ($user) {
            $user->setWaitingForMessage(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
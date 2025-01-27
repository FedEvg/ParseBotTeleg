<?php

namespace App\Service\Telegram\Command\Channel;

use App\DTO\Telegram\ChannelDTO;
use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;
use App\Service\ChannelService;
use App\Service\Telegram\AbstractBot;
use App\Service\Telegram\Command\Abstract\AbstractInteractiveCommand;
use App\Service\Telegram\ParserBot;
use App\Validator\ChannelValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class CreatePersonalChannel extends AbstractInteractiveCommand
{
    public function __construct(
        AbstractBot                               $bot,
        private readonly EntityManagerInterface   $entityManager,
        private readonly ChannelService           $channelService,
        private readonly ChannelValidationService $channelValidationService,
        private readonly ParserBot                $parserBot
    )
    {
        parent::__construct(
            $bot,
            '/add_personal_channel',
            'This command allows you to add a personal channel. After entering the command, please provide the tag of your personal channel.',
            'Please provide the channel tag (e.g., @telegram_channel_personal).'
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

            $this->checkNewsBotIsAdmin($channel);

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

    /**
     * @throws \Exception
     */
    private function checkNewsBotIsAdmin(ChannelDTO $channel): void
    {
        try {
            $administrators = $this->fetchChatAdministrators($channel->getId());
            $this->validateBotIsAdministrator($administrators);
        } catch (TelegramResponseException $e) {
            if (str_contains($e->getMessage(), 'member list is inaccessible')) {
                throw new Exception(
                    "The list of channel administrators is inaccessible. Please ensure that the bot @news_parser_free_bot is added as an administrator of your channel."
                );
            }

            throw new Exception('Error while checking channel administrators: ' . $e->getMessage());
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function fetchChatAdministrators(string $chatId): array
    {
        return $this->parserBot->getChatAdministrators([
            'chat_id' => $chatId,
        ]);
    }

    private function processChannel(ChannelDTO $channel, ?User $user): void
    {
        $this->channelService->createChannel($channel, true, $user);

        if ($user) {
            $user->setWaitingForMessage(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }

    /**
     * @throws Exception
     */
    private function validateBotIsAdministrator(array $administrators): void
    {
        foreach ($administrators as $admin) {
            if ($admin['user']['username'] === $this->parserBot->getUsername()) {
                if (empty($admin['can_post_messages'])) {
                    throw new Exception('The parser bot does not have permission to post messages.');
                }

                return;
            }
        }

        throw new Exception('The parser bot is not an administrator of the channel.');
    }
}
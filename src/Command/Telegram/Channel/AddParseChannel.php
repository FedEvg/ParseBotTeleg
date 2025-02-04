<?php

namespace App\Command\Telegram\Channel;

use App\Command\Telegram\AbstractResponseCommand;
use App\Entity\User;
use App\Service\ChannelService;
use App\Telegram\ConfigBot;
use Doctrine\ORM\EntityManagerInterface;

class AddParseChannel extends AbstractResponseCommand
{
    protected string $name = 'Add a channel for parse news to the bot';
    protected string $command = '/add_parse_channel';
    protected string $description = 'This command allows you to add parse channel to which the bot will post news. After entering the command, you need to specify the tag of your channel.';
    protected string $actionMessage = 'Enter the channel tag, for example: @telegram_channel_test';

    public function __construct(
        ConfigBot                                       $bot,
        protected readonly EntityManagerInterface $entityManager,
        private readonly ChannelService           $channelService,
    )
    {
        parent::__construct($bot);
    }

    public function handle($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];

        try {
            $user->setWaitingForMessage($this->getCommand());
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => $this->actionMessage,
            ]);
        } catch (\Exception $e) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Виникла помилка при створенні користувача: ' . $e->getMessage(),
            ]);

            return;
        }
    }

    public function handleResponseMessage($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];
        $tagChannel = $message['text'];

        try {
            $this->validateTagChannel($tagChannel);

            $channelInfo = $this->bot->getChat(['chat_id' => $tagChannel]);

            $this->validateChannelInfo($channelInfo);

            $this->processChannel($channelInfo, $user);

            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "Канал '{$channelInfo['title']}' успешно подтвержден и добавлен!",
            ]);
        } catch (\Exception $e) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Ошибка при проверке канала: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    private function validateTagChannel(string $tagChannel): void
    {
        if (empty($tagChannel)) {
            throw new \Exception('Channel tag cannot be empty.');
        }
    }

    /**
     * @throws \Exception
     */
    private function validateChannelInfo(object $channelInfo): void
    {
        if (empty($channelInfo['id'])) {
            throw new \Exception('Канал не найден или не существует.');
        }

        if ($channelInfo['type'] !== 'channel') {
            throw new \Exception('Это не канал. Убедитесь, что вы вводите правильный тег канала.');
        }

        $existingChannel = $this->channelService->findByTag($channelInfo['username']);
        if ($existingChannel) {
            throw new \Exception('Этот канал уже добавлен в систему.');
        }
    }

    private function processChannel(object $channelInfo, ?User $user): void
    {
        $this->channelService->createChannel($channelInfo, false, $user);

        if ($user) {
            $user->setWaitingForMessage(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}

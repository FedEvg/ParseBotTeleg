<?php

namespace App\Command\Telegram\Channel;

use App\Command\Telegram\AbstractResponseCommand;
use App\Entity\User;
use App\Repository\ChannelRepository;
use App\Service\ChannelService;
use Doctrine\ORM\EntityManagerInterface;
use Telegram\Bot\Api;

class DeleteMyChannel extends AbstractResponseCommand
{
    protected string $name = 'Видаляє ваш власний канал.';
    protected string $command = '/delete_my_channel';
    protected string $description = 'Ця команда дозволяє видалити власний канал бота, в який будуть публікуватися новини.';
    protected string $actionMessage = 'Enter the channel tag, for example: @telegram_channel_test';

    public function __construct(
        Api                                       $bot,
        protected readonly EntityManagerInterface $entityManager,
        private readonly ChannelService           $channelService,
        private readonly ChannelRepository        $channelRepository,
    )
    {
        parent::__construct($bot);
    }


    public function handle($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];

        try {
            $channels = $this->channelRepository->findByIsOwn(true);

            if (empty($channels)) {
                throw new \Exception('Немає доступних каналів.');
            }

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
                'text' => 'Виникла помилка при видаленні каналу: ' . $e->getMessage(),
            ]);

            return;
        }
    }

    public function handleResponseMessage($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];
        $tagChannel = $message['text'];

        try {
            if (empty($tagChannel)) {
                throw new \Exception('Тег канала не может быть пустым.');
            }

            $existingChannel = $this->channelService->findByTag($tagChannel);

            if (!$existingChannel) {
                throw new \Exception('Канал не найден в системе.');
            }

            $this->channelService->deleteChannelByTag($tagChannel);

            $user->setWaitingForMessage(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "Канал '{$tagChannel}' успешно удален из системы.",
            ]);

        } catch (\Exception $e) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Ошибка при удалении канала: ' . $e->getMessage(),
            ]);
        }
    }

}
<?php

namespace App\Command\Telegram\Tag;

use App\Command\Telegram\AbstractResponseCommand;
use App\Entity\User;
use App\Service\ChannelService;
use App\Service\TagService;
use App\Telegram\ConfigBot;
use Doctrine\ORM\EntityManagerInterface;

class DeleteTag extends AbstractResponseCommand
{
    protected string $name = 'Удалите тег с канала';
    protected string $command = '/delete_tag';
    protected string $description = 'Удалите тег с канала. Пример: @channel #tag';
    protected string $actionMessage = 'Введите тег канала и тег для удаления. Пример: @channel #tag';

    public function __construct(
        ConfigBot                                       $bot,
        protected readonly EntityManagerInterface $entityManager,
        private readonly ChannelService           $channelService,
        private readonly TagService               $tagService,
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
                'text' => 'Ошибка при обработке команды: ' . $e->getMessage(),
            ]);
        }
    }

    public function handleResponseMessage($message, ?User $user = null): void
    {
        $chatId = $message['chat']['id'];
        $inputText = $message['text'];

        try {
            $parts = preg_split('/\s+/', $inputText, 2, PREG_SPLIT_NO_EMPTY);
            if (count($parts) < 2) {
                throw new \Exception("Введите тег канала и тег для удаления. Пример: @channel #tag");
            }

            $channelTag = $parts[0];
            $tagToRemove = $parts[1];

            $this->validateChannelTag($channelTag);

            $channelInfo = $this->bot->getChat(['chat_id' => $channelTag]);

            $this->validateChannelInfo($channelInfo);

            $this->processRemoveTag($channelInfo, $tagToRemove, $user);

            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "Тег '{$tagToRemove}' успешно удален с канала '{$channelInfo['title']}'",
            ]);
        } catch (\Exception $e) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Ошибка при удалении тега: ' . $e->getMessage(),
            ]);
        }
    }

    private function validateChannelTag(string $channelTag): void
    {
        if (empty($channelTag) || $channelTag[0] !== '@') {
            throw new \Exception('Тег канала должен начинаться с @.');
        }
    }

    /**
     * @throws \Exception
     */
    private function validateChannelInfo(object $channelInfo): void
    {
        $existingChannel = $this->channelService->findByTag($channelInfo['username']);

        if (!$existingChannel) {
            throw new \Exception("Этот канал не найден в системе.");
        }
    }

    /**
     * @throws \Exception
     */
    private function processRemoveTag(object $channelInfo, string $tagToRemove, ?User $user): void
    {
        $tagToRemove = ltrim($tagToRemove, '#');

        // Найдем канал в базе данных
        $channel = $this->channelService->findByTag($channelInfo['username']);

        // Получим все теги канала
        $existingTags = $channel->getTags()->map(fn($tag) => $tag->getName())->toArray();

        // Если тег существует в списке, удаляем его
        if (in_array($tagToRemove, $existingTags)) {
            $tag = $this->tagService->findByName($tagToRemove);

            // Удаляем тег из канала
            if ($tag) {
                $channel->removeTag($tag);
                $this->entityManager->remove($tag);
                $this->entityManager->persist($channel);
                $this->entityManager->flush();
            } else {
                throw new \Exception("Тег '{$tagToRemove}' не найден в системе.");
            }
        } else {
            throw new \Exception("Тег '{$tagToRemove}' не привязан к каналу.");
        }

        // Если пользователь был найден, сбрасываем ожидание
        if ($user) {
            $user->setWaitingForMessage(null);
            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();
    }

}

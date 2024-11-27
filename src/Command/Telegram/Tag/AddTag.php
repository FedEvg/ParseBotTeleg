<?php

namespace App\Command\Telegram\Tag;

use App\Command\Telegram\AbstractResponseCommand;
use App\Entity\User;
use App\Service\ChannelService;
use App\Service\TagService;
use Doctrine\ORM\EntityManagerInterface;
use Telegram\Bot\Api;

class AddTag extends AbstractResponseCommand
{
    protected string $name = 'Добавьте тег к каналу';
    protected string $command = '/add_tag';
    protected string $description = 'Добавьте тег каналу и теги через пробел. Пример: @channel #tag1 #tag2';
    protected string $actionMessage = 'Введите тег канала и теги через пробел. Пример: @channel #tag1 #tag2';

    public function __construct(
        Api                                       $bot,
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
                'text' => 'Виникла помилка при налаштуванні тега: ' . $e->getMessage(),
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
                throw new \Exception("Введите тег канала и хотя бы один хештег. Пример: @channel #tag1 #tag2");
            }

            $channelTag = $parts[0];
            $tags = array_filter(explode('#', $parts[1]), fn($tag) => !empty(trim($tag)));

            $this->validateChannelTag($channelTag);

            $channelInfo = $this->bot->getChat(['chat_id' => $channelTag]);

            if (empty($tags)) {
                throw new \Exception("Не удалось найти хештеги. Добавьте хотя бы один тег.");
            }

            $this->processChannelAndTags($channelInfo, $tags, $user);

            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "Канал '{$channelInfo['title']}' успешно обновлен с тегами: " . implode(', ', array_map(fn($tag) => "#$tag", $tags)),
            ]);
        } catch (\Exception $e) {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Ошибка при добавлении тегов: ' . $e->getMessage(),
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
    private function processChannelAndTags(object $channelInfo, array $tags, ?User $user): void
    {
        $channel = $this->channelService->findByTag($channelInfo['username']);

        if (!$channel) {
            throw new \Exception("Канал с тегом {$channelInfo['username']} не найден. Сначала добавьте канал.");
        }

        $existingTags = $channel->getTags()->map(fn($tag) => $tag->getName())->toArray();

        $newTags = array_diff(array_map('trim', $tags), $existingTags);

        if (empty($newTags)) {
            throw new \Exception("Все введенные теги уже привязаны к каналу.");
        }

        foreach ($newTags as $tag) {
            if (!empty($tag)) {
                $this->tagService->createTag($tag, $channel);
            }
        }

        if ($user) {
            $user->setWaitingForMessage(null);
            $this->entityManager->persist($user);
        }

        $this->entityManager->persist($channel);
        $this->entityManager->flush();
    }
}

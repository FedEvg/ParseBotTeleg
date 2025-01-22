<?php

namespace App\Command\Telegram\Channel;

use App\Command\Telegram\AbstractResponseCommand;
use App\Entity\User;
use App\Service\ChannelService;
use App\Telegram\ConfigBot;
use App\Telegram\ParseBot;
use Doctrine\ORM\EntityManagerInterface;
use Telegram\Bot\Exceptions\TelegramResponseException;
use Telegram\Bot\Exceptions\TelegramSDKException;

class AddMyChannel extends AbstractResponseCommand
{
    protected string $name = 'Add a channel to the bot';
    protected string $command = '/add_my_channel';
    protected string $description = 'This command allows you to add your own channel to which the bot will post news. After entering the command, you need to specify the tag of your channel.';
    protected string $actionMessage = 'Enter the channel tag, for example: @telegram_channel_test';

    public function __construct(
        ConfigBot                                 $bot,
        readonly ParseBot                         $parseBot,
        protected readonly EntityManagerInterface $entityManager,
        private readonly ChannelService           $channelService,
        private readonly string                   $newsBotUsername,
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

            $this->checkNewsBotIsAdmin($channelInfo);

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
        $this->channelService->createChannel($channelInfo, true, $user);

        if ($user) {
            $user->setWaitingForMessage(null);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }


    /**
     * @throws TelegramSDKException
     * @throws \Exception
     */
    private function checkNewsBotIsAdmin(object $channelInfo): void
    {
        try {
            $administrators = $this->parseBot->getChatAdministrators([
                'chat_id' => $channelInfo['id'],
            ]);

            foreach ($administrators as $admin) {
                if ($admin['user']['username'] === $this->newsBotUsername) {
                    if (empty($admin['can_post_messages'])) {
                        throw new \Exception('Новинний бот не має дозволу на публікацію повідомлень.');
                    }

                    return;
                }
            }

            throw new \Exception('Новинний бот не є адміністратором канала.');
        } catch (TelegramResponseException $e) {
            if (str_contains($e->getMessage(), 'member list is inaccessible')) {
                throw new \Exception(
                    "Список администраторов канала недоступен. Переконайтеся, что бот @news_parser_free_bot добавлен в администраторы вашего канала."
                );
            }

            throw new \Exception('Ошибка при проверке администраторов канала: ' . $e->getMessage());
        }
    }

}

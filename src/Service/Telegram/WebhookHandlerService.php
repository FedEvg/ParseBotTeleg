<?php

namespace App\Service\Telegram;

use App\Command\Telegram\AbstractCommand;
use App\Command\Telegram\AbstractResponseCommand;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use Exception;
use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Telegram\Bot\Exceptions\TelegramSDKException;

readonly class WebhookHandlerService
{
    public function __construct(
        private AbstractBot    $bot,
        private iterable       $commands,
        private UserService    $userService
    )
    {
    }

    /**
     * @return AbstractBot
     */
    public function getBot(): AbstractBot
    {
        return $this->bot;
    }

    /**
     *
     * @param array $update
     * @return void
     * @throws TelegramSDKException
     */
    public function handleCommandMessage(array $update): void
    {
        $this->handleUpdate($update, $update["text"]);
    }

    /**
     *
     * @param array $update
     * @return void
     * @throws TelegramSDKException
     */
    public function handleCommandCallback(array $update): void
    {
        $this->handleUpdate($update['message'], $update["data"]);
    }

    /**
     *
     * @param array $update
     * @param string $text
     * @return void
     * @throws TelegramSDKException
     */
    private function handleUpdate(array $update, string $text): void
    {
        try {
            $user = $this->getOrCreateUser($update);
            $this->checkUserRole($user);

            $command = $this->findCommand($text);

            if ($command === null) {
                $this->handleNoCommand($update, $user);
            } else {
                $command->handle($update, $user);
            }
        } catch (Exception $e) {
            $this->handleError($update['chat']['id'], $e);
        }
    }

    /**
     *
     * @param array $update
     * @param User $user
     * @return void
     * @throws Exception
     */
    private function handleNoCommand(array $update, User $user): void
    {
        $waitingMessage = $user->getWaitingForMessage();

        if (empty($waitingMessage)) {
            throw new AssetNotFoundException('Команда не знайдена або відсутня команда, що чекає на відповідь.');
        }

        $command = $this->findCommand($waitingMessage);

        if ($command instanceof AbstractResponseCommand) {
            $command->handleResponseMessage($update, $user);
        } else {
            throw new \LogicException('Команда не підтримує обробку відповіді.');
        }
    }

    /**
     *
     * @param string $text
     * @return AbstractCommand|null
     */
    private function findCommand(string $text): ?AbstractCommand
    {
        foreach ($this->commands as $command) {
            if ($command->getCommand() === $text) {
                return $command;
            }
        }

        return null;
    }

    /**
     * @param array $message
     * @return User
     */
    private function getOrCreateUser(array $message): User
    {
        $userId = $message['from']['id'];
        return $this->userService->createUser($message['chat']['username'], $userId);
    }

    /**
     *
     * @param User $user
     * @return void
     * @throws Exception
     */
    private function checkUserRole(User $user): void
    {
        if (!$user->getRoles() || !in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            throw new Exception('У вас немає прав для цієї дії.');
        }
    }

    /**
     *
     * @param int $chatId
     * @param Exception $e
     * @return void
     * @throws TelegramSDKException
     */
    private function handleError(int $chatId, Exception $e): void
    {
        $this->bot->sendChatMessage($chatId, 'Error: ' . $e->getMessage());
    }
}

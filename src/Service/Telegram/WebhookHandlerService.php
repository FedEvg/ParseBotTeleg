<?php

namespace App\Service\Telegram;

use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;
use App\Service\Telegram\Command\Abstract\AbstractCommand;
use App\Service\Telegram\Command\Abstract\AbstractInteractiveCommand;
use App\Service\UserService;
use Exception;
use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Telegram\Bot\Exceptions\TelegramSDKException;

readonly class WebhookHandlerService
{
    public function __construct(
        private AbstractBot $bot,
        private iterable    $commands,
        private UserService $userService
    )
    {
    }

    public function getBot(): AbstractBot
    {
        return $this->bot;
    }


    /**
     * @throws TelegramSDKException
     */
    public function handleCommandMessage(WebhookDTO $webhook): void
    {
        $this->handleUpdate($webhook);
    }

//    public function handleCommandCallback(WebhookDTO $webhook): void
//    {
//        $this->handleUpdate($webhook['message'], $webhook["data"]);
//    }

    /**
     * @throws TelegramSDKException
     * @throws Exception
     */
    public function handleUpdate(WebhookDTO $webhook): void
    {
        try {
            $user = $this->getOrCreateUser($webhook->getChat()->getId(), $webhook->getChat()->getUserName());
            $this->checkUserRole($user);

            $command = $this->findCommand($webhook->getText());

            if ($command === null) {
                $this->handleNoCommand($webhook, $user);
            } else {
                $command->executeCommand($webhook, $user);
            }
        } catch (Exception $e) {
            $this->handleError($webhook->getChat()->getId(), $e);
        } catch (TelegramSDKException $e) {
            throw new Exception('Помилка відправлення повідомлення в телеграм.');
        }
    }

    private function handleNoCommand(WebhookDTO $webhook, User $user): void
    {
        $waitingMessage = $user->getWaitingForMessage();

        if (empty($waitingMessage)) {
            throw new AssetNotFoundException('Команда не знайдена або відсутня команда, що чекає на відповідь.');
        }

        $command = $this->findCommand($waitingMessage);

        if ($command instanceof AbstractInteractiveCommand) {
            $command->executeInteractiveCommand($webhook, $user);
        } else {
            throw new \LogicException('Команда не підтримує обробку відповіді.');
        }
    }

    private function findCommand(string $text): ?AbstractCommand
    {
        foreach ($this->commands as $command) {
            if ($command->getName() === $text) {
                return $command;
            }
        }

        return null;
    }

    private function getOrCreateUser(int $userId, string $username): User
    {
        return $this->userService->createUser($username, $userId);
    }

    /**
     * @throws Exception
     */
    private function checkUserRole(User $user): void
    {
        if (!$user->getRoles() || !in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            throw new Exception('У вас немає прав для цієї дії.');
        }
    }

    /**
     * @throws TelegramSDKException
     */
    private function handleError(int $chatId, Exception $e): void
    {
        $this->bot->sendChatMessage($chatId, 'Error: ' . $e->getMessage());
    }
}

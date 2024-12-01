<?php

namespace App\Command\Telegram;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\UserService;
use App\Telegram\ConfigBot;
use Symfony\Component\Asset\Exception\AssetNotFoundException;

class CommandHandler
{
    public function __construct(
        protected ConfigBot             $configBot,
        protected iterable              $commands,
        private readonly UserRepository $userRepository,
        private readonly UserService    $userService,
    )
    {
    }

    public function handleCommandMessage($update): void
    {
        try {
            $this->searchCommand($update, $update["text"]);
        } catch (\Exception $e) {
            $this->configBot->sendChatMessage(
                $update['chat']['id'],
                'Error: ' . $e->getMessage()
            );
        }
    }

    public function handleCommandCallback($update): void
    {
        try {
            $this->searchCommand($update['message'], $update["data"]);
        } catch (\Exception $e) {
            $this->configBot->sendChatMessage(
                $update['message']['chat']['id'],
                'Error: ' . $e->getMessage()
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function searchCommand($update, $text): void
    {
        $command = $this->findCommand($text);
        $userId = $update['from']['id'];

        $user = $this->userRepository->findByUserId($userId) ?? $this->createNewUser($update);

        $this->checkUserRole($user);

        if ($command === null) {
            $waitingMessage = $user->getWaitingForMessage();

            if (empty($waitingMessage)) {
                throw new AssetNotFoundException('Команда не найдена либо нет команды, которая ожидает ответ от вас.');
            }

            $command = $this->findCommand($waitingMessage);

            if ($command instanceof AbstractResponseCommand) {
                $command->handleResponseMessage($update, $user);
            } else {
                throw new \LogicException('Команда не поддерживает обработку ответного сообщения.');
            }

            return;
        }

        $command->handle($update, $user);
    }


    private function findCommand($text): ?AbstractCommand
    {
        foreach ($this->commands as $command) {
            if ($command->getCommand() === $text) {
                return $command;
            }
        }

        return null;
    }

    private function createNewUser($message): User
    {
        $username = $message['chat']['username'];
        $userId = $message['from']['id'];

        if ($this->userRepository->find($userId)) {
            return $this->userRepository->find($userId);
        }

        $this->userService->createUser($username, $userId);

        return $this->userRepository->find($userId);
    }

    /**
     * @throws \Exception
     */
    private function checkUserRole($user): void
    {
        if (!$user->getRoles() || !in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true)) {
            throw new \Exception('У вас немає прав для цієї дії.');
        }
    }
}
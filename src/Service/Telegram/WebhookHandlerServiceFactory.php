<?php

namespace App\Service\Telegram;

use App\Repository\UserRepository;
use App\Service\UserService;

readonly class WebhookHandlerServiceFactory
{
    public function __construct(
        private iterable    $bots,
        private iterable    $commands,
        private UserService $userService,
    )
    {
    }

    public function create(string $botType): WebhookHandlerService
    {
        foreach ($this->bots as $bot) {
            if ($bot instanceof AbstractBot && $bot->getType() === $botType) {
                return new WebhookHandlerService($bot, $this->commands, $this->userService);
            }
        }

        throw new \InvalidArgumentException("Unknown bot type: $botType");
    }
}
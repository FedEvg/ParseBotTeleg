<?php

namespace App\Service\Telegram\Command\Abstract;

use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;
use App\Service\Telegram\AbstractBot;
use App\Service\Telegram\Command\Interfaces\CommandInterface;

abstract class AbstractCommand implements CommandInterface
{
    public function __construct(
        protected AbstractBot $bot,
        protected string      $name,
        protected string      $description
    )
    {
    }

    abstract public function executeCommand(WebhookDTO $webhook, ?User $user = null): void;

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getBot(): AbstractBot
    {
        return $this->bot;
    }
}
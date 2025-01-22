<?php

namespace App\Service\Telegram\Command\Abstract;

use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;
use App\Service\Telegram\AbstractBot;
use App\Service\Telegram\Command\Interfaces\InteractiveCommandInterface;

abstract class AbstractInteractiveCommand extends AbstractCommand implements InteractiveCommandInterface
{
    public function __construct(
        AbstractBot               $bot,
        protected readonly string $name,
        protected readonly string $description,
        protected readonly string $messageToAction,
    )
    {
        parent::__construct($bot, $name, $description);
    }

    public function getMessageToAction(): string
    {
        return $this->messageToAction;
    }

    abstract public function executeInteractiveCommand(WebhookDTO $webhook, ?User $user = null): void;
}
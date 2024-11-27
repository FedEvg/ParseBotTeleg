<?php

namespace App\Command\Telegram;

use App\Entity\User;

abstract class AbstractResponseCommand extends AbstractCommand implements ResponseCommandInterface
{
    protected string $actionMessage;

    public function getActionMessage(): string
    {
        return $this->actionMessage;
    }

    abstract public function handleResponseMessage($message, ?User $user = null): void;
}
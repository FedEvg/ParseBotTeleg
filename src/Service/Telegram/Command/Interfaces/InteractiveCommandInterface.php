<?php

namespace App\Service\Telegram\Command\Interfaces;

use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;

interface InteractiveCommandInterface
{
    public function executeInteractiveCommand(WebhookDTO $webhook, ?User $user = null): void;
}

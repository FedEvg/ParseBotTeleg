<?php

namespace App\Service\Telegram\Command\Interfaces;

use App\DTO\Telegram\WebhookDTO;
use App\Entity\User;

interface CommandInterface
{
    public function executeCommand(WebhookDTO $webhook, ?User $user = null): void;
}

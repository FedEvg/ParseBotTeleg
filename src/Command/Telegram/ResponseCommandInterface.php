<?php

namespace App\Command\Telegram;

use App\Entity\User;

interface ResponseCommandInterface
{
    public function handleResponseMessage($message, ?User $user = null);
}
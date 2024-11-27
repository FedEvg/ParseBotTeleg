<?php

namespace App\Command\Telegram;

use App\Entity\User;

interface CommandInterface
{
    public function handle($message, ?User $user = null);
}
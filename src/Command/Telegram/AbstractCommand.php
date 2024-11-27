<?php

namespace App\Command\Telegram;

use App\Entity\User;
use Telegram\Bot\Api;

abstract class AbstractCommand implements CommandInterface
{

    public function __construct(
        protected Api $bot,
    )
    {
        if (!$this->bot) {
            throw new \Exception('Bot object is not injected correctly.');
        }
    }

    protected string $name;

    protected string $command;
    protected string $description;

    abstract public function handle($message, ?User $user = null);

    public function getName(): string
    {
        return $this->name;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
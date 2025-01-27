<?php

namespace App\DTO\Telegram;

use Symfony\Component\Validator\Constraints as Assert;

class ChannelDTO
{
    #[Assert\NotBlank(message: 'Channel not found or does not exist.')]
    #[Assert\Type('integer')]
    private int $id;

    private string $title;

    private string $userName;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Choice(choices: ['channel'], message: 'This is not a channel. Make sure you enter the correct channel tag.')]
    private string $type;

    private ?string $description = null;

    private ?string $linkedChatId = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getUserName(): string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getLinkedChatId(): string
    {
        return $this->linkedChatId;
    }

    public function setLinkedChatId(?string $linkedChatId): static
    {
        $this->linkedChatId = $linkedChatId;

        return $this;
    }
}

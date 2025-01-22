<?php

namespace App\DTO\Telegram;

class WebhookDTO
{
    private int $messageId;
    private FromDTO $from;
    private ChatDTO $chat;
    private int $date;
    private string $text;

    public function getMessageId(): int
    {
        return $this->messageId;
    }

    public function setMessageId(int $messageId): static
    {
        $this->messageId = $messageId;

        return $this;
    }

    public function getFrom(): FromDTO
    {
        return $this->from;
    }

    public function setFrom(FromDTO $from): static
    {
        $this->from = $from;

        return $this;
    }

    public function getChat(): ChatDTO
    {
        return $this->chat;
    }

    public function setChat(ChatDTO $chat): static
    {
        $this->chat = $chat;

        return $this;
    }

    public function getDate(): int
    {
        return $this->date;
    }

    public function setDate(int $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }
}
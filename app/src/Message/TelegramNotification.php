<?php

namespace App\Message;

/**
 * Я в курсе, что в PHP8.4 ввели хуки для свойств классов,
 * но я буду использовать привычные геттеры и сеттеры.
 */
final class TelegramNotification
{
    /**
     * @var int $chatId
     */
    private int $chatId;

    /**
     * @var string $message
     */
    private string $message;

    /**
     * @return int
     */
    public function getChatId(): int
    {
        return $this->chatId;
    }

    /**
     * @param int $chatId
     * @return self
     */
    public function setChatId(int $chatId): self
    {
        $this->chatId = $chatId;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}

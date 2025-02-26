<?php

namespace App\MessageHandler;

use App\Message\TelegramNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TelegramNotificationHandler
{
    public function __invoke(TelegramNotification $message): void
    {
        // Если воркер только один, то достаточно просто добавить задержку прямо здесь.
        // Если воркеров несколько, то лучше использовать DelayStamp() при добавлении сообщения в очередь.
        usleep(100_000);

        // Пока просто вывод сообщения в консоль
        echo sprintf(
            'Chat ID: %d, Message: %s' . PHP_EOL . PHP_EOL,
            $message->getChatId(), $message->getMessage()
        );
    }
}

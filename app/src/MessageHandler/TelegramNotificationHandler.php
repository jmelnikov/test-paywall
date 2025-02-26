<?php

namespace App\MessageHandler;

use App\Message\TelegramNotification;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
final readonly class TelegramNotificationHandler
{
    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function __invoke(TelegramNotification $message): void
    {
        // Если воркер только один, то достаточно просто добавить задержку прямо здесь.
        // Если воркеров несколько, то лучше использовать DelayStamp() при добавлении сообщения в очередь.
        usleep(100_000);

        // Пока просто вывод сообщения в консоль
        $this->sendMessage($message->getChatId(), $message->getMessage());
    }

    /**
     * @param int $chatId
     * @param string $message
     * @return void
     *
     * Так как это пример, то я просто отправляю сообщение с помощью HTTP-запроса.
     * Но лучше использовать библиотеку для работы с Telegram API.
     */
    private function sendMessage(int $chatId, string $message): void
    {
        try {
            $this->httpClient->request('POST', 'https://api.telegram.org/bot' . $_ENV['TELEGRAM_BOT_TOKEN'] . '/sendMessage', [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ],
            ]);
        } catch (TransportExceptionInterface $exception) {
            // Здесь что-то делаем, если сообщение не отправилось.
            // Сейчас просто выводим сообщение в консоль.
            echo $exception->getMessage();
        }
    }
}

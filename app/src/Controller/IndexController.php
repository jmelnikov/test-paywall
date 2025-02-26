<?php

namespace App\Controller;

use App\Message\TelegramNotification;
use App\Service\PaymentHandler;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(name: 'index.')]
final class IndexController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    )
    {
    }

    #[Route(path: '/test', name: 'test', methods: ['POST'])]
    public function test(PaymentHandler $paymentHandler, Request $request): JsonResponse
    {
        try {
            $paymentHandler->handlePaymentJson($request->getContent());
        } catch (Exception $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ]);
        }

        return $this->json([
            'success' => true,
            'message' => $this->translator->trans('success.request',
                ['%gateway%' => PaymentHandler::class],
                'messages'),
        ]);
    }

    #[Route(path: '/test', name: 'test.message', methods: ['GET'])]
    public function testMessage(MessageBusInterface $messageBus): JsonResponse
    {
        $message = new TelegramNotification()
            ->setChatId(123)
            ->setMessage('Тестовое сообщение');

        $messageBus->dispatch($message);

        return $this->json([
            'success' => true,
            'message' => 'Тестовое сообщение отправлено в очередь',
        ]);
    }
}

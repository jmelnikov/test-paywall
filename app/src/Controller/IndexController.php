<?php

namespace App\Controller;

use App\Service\PaymentHandler;
use App\Service\SecondPaymentHandler;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
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
            $paymentHandler->handlePayment($request);
        } catch (Exception|ExceptionInterface $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $paymentHandler->getErrorCode());
        }

        return $this->json([
            'success' => true,
            'message' => $this->translator->trans('success.request',
                ['%gateway%' => PaymentHandler::class],
                'messages'),
        ]);
    }

    #[Route(path: '/test2', name: 'test2', methods: ['POST'])]
    public function test2(SecondPaymentHandler $paymentSecondHandler, Request $request): JsonResponse
    {
        try {
            $paymentSecondHandler->handlePayment($request);
        } catch (Exception|ExceptionInterface $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], $paymentSecondHandler->getErrorCode());
        }

        return $this->json([
            'success' => true,
            'message' => $this->translator->trans('success.request',
                ['%gateway%' => SecondPaymentHandler::class],
                'messages'),
        ]);
    }
}

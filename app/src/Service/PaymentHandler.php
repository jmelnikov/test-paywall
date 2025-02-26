<?php

namespace App\Service;

use Exception;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentHandler
{
    private const array PAYMENT_STATUSES = [
        'authorized',
        'confirmed',
        'rejected',
        'refunded',
    ];

    private const array PAYMENT_CURRENCIES = [
        'RUB',
        'USD',
        'EUR',
    ];

    private const array PAYMENT_LANGUAGES = [
        'ru',
        'en',
    ];

    private array $paymentData;

    public function __construct(
        private TranslatorInterface $translator,
        private LocaleSwitcher      $localeSwitcher,
        private PaymentProcessor    $paymentProcessor,
    )
    {
    }

    /**
     * @param string $jsonData
     * @return void
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function handlePaymentJson(string $jsonData): void
    {
        $this->paymentData = json_decode($jsonData, true);

        if (empty($this->paymentData)) {
            throw new Exception($this->translator->trans('error.request.empty',
                [], 'messages'));
        }

        // В первую очередь вызываем проверку языка пользователя.
        // И, если он корректный, устанавливаем его для сообщений.
        $this->validateLanguageCode();

        // Проверяем все остальные параметры платежа.
        $this->validateToken();
        $this->validateStatus();
        $this->validateOrderId();
        $this->validateAmount();
        $this->validateCurrency();
        $this->validateErrorCode();
        // Значение pan не проверяем
        $this->validateUserId();

        // Обрабатываем платеж
        $this->processPayment();
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateLanguageCode(): void
    {
        if (!in_array($this->paymentData['language_code'], self::PAYMENT_LANGUAGES)) {
            throw new Exception($this->translator->trans('error.payment.unknown_language',
                [], 'messages'));
        }

        $this->localeSwitcher->setLocale($this->paymentData['language_code']);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateToken(): void
    {
        // Тест проверяет только формат токена "цифры-дефис-цифры".
        if (!preg_match('/^\d+-\d+$/', $this->paymentData['token'])) {
            throw new Exception($this->translator->trans('error.payment.invalid_token',
                [], 'messages'));
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateStatus(): void
    {
        if (!in_array($this->paymentData['status'], self::PAYMENT_STATUSES)) {
            throw new Exception($this->translator->trans('error.payment.unknown_status',
                [], 'messages'));
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateOrderId(): void
    {
        if (!preg_match('/^\d+$/', $this->paymentData['order_id'])) {
            throw new Exception($this->translator->trans('error.payment.invalid_order_id',
                [], 'messages'));
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateAmount(): void
    {
        if (!preg_match('/^\d+$/', $this->paymentData['amount'])) {
            throw new Exception($this->translator->trans('error.payment.invalid_amount',
                [], 'messages'));
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateCurrency(): void
    {
        if (!in_array($this->paymentData['currency'], self::PAYMENT_CURRENCIES)) {
            throw new Exception($this->translator->trans('error.payment.unknown_currency',
                [], 'messages'));
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateErrorCode(): void
    {
        if (!preg_match('/^\d+$/', $this->paymentData['error_code']) && $this->paymentData['error_code'] !== null) {
            throw new Exception($this->translator->trans('error.payment.invalid_error_code',
                [], 'messages'));
        }
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validateUserId(): void
    {
        if (!preg_match('/^\d+$/', $this->paymentData['user_id'])) {
            throw new Exception($this->translator->trans('error.payment.invalid_user_id',
                [], 'messages'));
        }
    }

    /**
     * @return void
     * @throws ExceptionInterface
     */
    private function processPayment(): void
    {
        // Здесь можно что угодно делать с данными платежа.
        // Например, сохранить его в лог платежей.
        // Но я просто передаю данные в PaymentProcessor для обработки.
        $this->paymentProcessor->processPayment(
            $this->paymentData['user_id'],
            $this->paymentData['status']
        );
    }
}
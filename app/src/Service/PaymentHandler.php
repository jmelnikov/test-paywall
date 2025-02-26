<?php

namespace App\Service;

use App\Service\Interface\PaymentHandlerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentHandler implements PaymentHandlerInterface
{
    private const array PAYMENT_STATUSES = [
        'authorized' => 'success',
        'confirmed' => 'success',
        'rejected' => 'error',
        'refunded' => 'error',
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
     * @param Request $request
     * @return void
     * @throws ExceptionInterface
     * @throws Exception
     */
    public function handlePayment(Request $request): void
    {
        $this->paymentData = json_decode($request->getContent(), true);

        if (empty($this->paymentData)) {
            throw new Exception($this->translator->trans('error.request.empty',
                [], 'messages'));
        }

        // В первую очередь вызываем проверку языка пользователя.
        // И, если он корректный, устанавливаем его для сообщений.
        $this->validateLanguageCode();

        // Проверяем все остальные параметры платежа.
        // Сейчас я выбрасываю исключение для каждой ошибки в отдельности.
        // В реальном проекте можно сделать накопление ошибок и возвращаться их списком.
        $this->validateToken();
        $this->validateStatus();
        $this->validateOrderId();
        $this->validateAmount();
        $this->validateCurrency();
        $this->validateErrorCode();
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
        // Получаем язык из параметров платежа.
        // Если его нет, то используем русский язык по умолчанию.
        $languageCode = $this->paymentData['language_code'] ?? 'ru';

        // Проверяем, что язык известен.
        if (!in_array($languageCode, static::PAYMENT_LANGUAGES)) {
            throw new Exception($this->translator->trans('error.payment.unknown_language',
                [], 'messages'));
        }

        // Здесь устанавливаем локаль для всего приложения.
        $this->localeSwitcher->setLocale($languageCode);
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
        // Проверяем, что статус платежа известен.
        if (!in_array($this->paymentData['status'], array_keys(static::PAYMENT_STATUSES))) {
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
        // Проверяем, что ID заказа состоит только из цифр.
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
        // Проверяем, что сумма платежа состоит только из цифр.
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
        // Проверяем, что валюта платежа известна.
        if (!in_array($this->paymentData['currency'], static::PAYMENT_CURRENCIES)) {
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
        // Проверяем, что код ошибки платежа состоит только из цифр или равен null.
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
        // Проверяем, что ID пользователя состоит только из цифр.
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
        // Но я просто передаю в PaymentProcessor ID пользователя и статус платежа.
        $this->paymentProcessor->processPayment(
            $this->paymentData['user_id'],
            static::PAYMENT_STATUSES[$this->paymentData['status']]
        );
    }
}

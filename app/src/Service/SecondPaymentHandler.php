<?php

namespace App\Service;

use App\Service\Interface\PaymentHandlerInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SecondPaymentHandler implements PaymentHandlerInterface
{
    // Здесь мапить статусы самих на себя излишне, но для единообразия оставил.
    private const array PAYMENT_STATUSES = [
        'success' => 'success',
        'error' => 'error',
    ];

    // Массив с данными платежа
    private array $paymentData;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly PaymentProcessor    $paymentProcessor,
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
        // Здесь я тоже вручную валидирую данные платежа.
        // Но можно использовать валидацию формы или другие инструменты Symfony.
        $this->setUserId($request);
        $this->setPaymentStatus($request);

        $this->processPayment();
    }

    /**
     * @param Request $request
     * @return void
     * @throws Exception
     */
    private function setUserId(Request $request): void
    {
        try {
            $this->paymentData['user_id'] = $request->request->getInt('user_id');
        } catch (Exception $exception) {
            // Здесь может выброшено исключение, если пользователь в ID не передал число.
            throw new Exception($this->translator->trans('error.payment.invalid_user_id',
                [], 'messages'));
        }

        if ($this->paymentData['user_id'] === 0) {
            throw new Exception($this->translator->trans('error.payment.invalid_user_id',
                [], 'messages'));
        }
    }

    /**
     * @param Request $request
     * @return void
     * @throws Exception
     */
    private function setPaymentStatus(Request $request): void
    {
        $this->paymentData['status'] = $request->request->get('status');

        if (!in_array($this->paymentData['status'], array_keys(static::PAYMENT_STATUSES))) {
            throw new Exception($this->translator->trans('error.payment.unknown_status',
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

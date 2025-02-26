<?php

namespace App\Service\Interface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\ExceptionInterface;

interface PaymentHandlerInterface
{
    /**
     * @param Request $request
     * @return void
     * @throws ExceptionInterface
     */
    public function handlePayment(Request $request): void;
}

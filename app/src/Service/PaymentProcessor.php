<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\User;
use App\Message\TelegramNotification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentProcessor
{
    public function __construct(
        private UserRepository         $userRepository,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface    $messageBus,
        private TranslatorInterface    $translator,
    )
    {
    }

    /**
     * @param int $userId
     * @param string $paymentStatus
     * @return void
     * @throws ExceptionInterface
     */
    public function processPayment(int $userId, string $paymentStatus): void
    {
        $user = $this->userRepository->findOneBy(['tg_id' => $userId]);
        if (!$user instanceof User) {
            $user = new User();
            $user->setTgId($userId);
            $this->entityManager->persist($user);
        }

        switch ($paymentStatus) {
            // Если платёж авторизован или подтверждён, то считаем это удачным платежом.
            case 'authorized':
            case 'confirmed':
                $this->createPayment($user);
                break;
            case 'rejected':
                // Если платеж отклонен, отправляем пользователю сообщение об ошибке.
                $this->messageBus->dispatch(new TelegramNotification()
                    ->setChatId($user->getTgId())
                    ->setMessage($this->translator->trans('payment.rejected',
                        [], 'messages')));
                break;
            case 'refunded':
                // Если платеж возвращен, отправляем пользователю сообщение о возврате.
                $this->messageBus->dispatch(new TelegramNotification()
                    ->setChatId($user->getTgId())
                    ->setMessage($this->translator->trans('payment.refunded',
                        [], 'messages')));
                break;
        }
    }

    /**
     * @param User $user
     * @return void
     * @throws ExceptionInterface
     */
    private function createPayment(User $user): void
    {
        $payment = new Payment()->setUser($user);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        if ($user->getPayments()->count() === 0) {
            // Если это первый платеж пользователя, отправляем ему сообщение о подписке.
            $this->messageBus->dispatch(new TelegramNotification()
                ->setChatId($user->getTgId())
                ->setMessage($this->translator->trans('subscription.new',
                    ['%user_id%' => $user->getTgId()], 'messages')));
        } else {
            // Если пользователь уже платил, отправляем ему сообщение о продлении подписки.
            $this->messageBus->dispatch(new TelegramNotification()
                ->setChatId($user->getTgId())
                ->setMessage($this->translator->trans('subscription.renew',
                    ['%user_id%' => $user->getTgId()], 'messages')));
        }
    }
}

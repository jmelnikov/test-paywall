<?php

namespace App\Service;

use App\Entity\Payment;
use App\Entity\User;
use App\Message\TelegramNotification;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class PaymentProcessor
{
    // Статусы платежей, которые может обрабатывать приложение.
    public const array PAYMENT_STATUSES = [
        'success',
        'error',
    ];

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
     * @throws Exception
     */
    public function processPayment(int $userId, string $paymentStatus): void
    {
        $user = $this->userRepository->findOneBy(['tg_id' => $userId]);
        if (!$user instanceof User) {
            $user = new User();
            $user->setTgId($userId);
            $this->entityManager->persist($user);
        }

        if (!in_array($paymentStatus, self::PAYMENT_STATUSES)) {
            throw new Exception($this->translator->trans('error.payment.unknown_status',
                [], 'messages'));
        }

        switch ($paymentStatus) {
            // Если платеж успешен, создаем платеж для пользователя.
            case 'success':
                $this->createPayment($user);
                break;
            // Если платеж неуспешен, отправляем пользователю сообщение об ошибке.
            case 'error':
                $this->messageBus->dispatch(new TelegramNotification()
                    ->setChatId($user->getTgId())
                    ->setMessage($this->translator->trans('telegram.notification.error',
                        [], 'messages')));
                break;
        }
    }

    /**
     * @param User $user
     * @return void
     * @throws ExceptionInterface
     *
     * Создание платежа для пользователя.
     * Отправка сообщения о подписке или продлении подписки.
     */
    private function createPayment(User $user): void
    {
        // Создаем платеж для пользователя и сохраняем его в базе данных.
        $payment = new Payment()->setUser($user);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        // Из-за особенности работы EntityManager,
        // пользователь уже прикреплен к платежу,
        // но платеж еще не прикреплен к пользователю.
        // Так что сравниваем количество платежей пользователя с 0.
        $message = '';
        if ($user->getPayments()->count() === 0) {
            // Если это первый платеж пользователя, отправляем ему сообщение о подписке.
            $message = $this->translator->trans('telegram.notification.success.new',
                ['%user_id%' => $user->getTgId()], 'messages');
        } else {
            // Если пользователь уже платил, отправляем ему сообщение о продлении подписки.
            $message = $this->translator->trans('telegram.notification.success.renew',
                ['%user_id%' => $user->getTgId()], 'messages');
        }

        $this->messageBus->dispatch(new TelegramNotification()
            ->setChatId($user->getTgId())
            ->setMessage($message));
    }
}

<?php

namespace App\Controller;

use App\Exception\TransactionException;
use App\Service\ExchangeRateUpdater;
use App\Service\TransactionFactory;
use App\Service\TransactionPerformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/transfer', name: 'app_transaction')]
class PerformTransactionController extends AbstractController
{
    use ErrorTrait;

    public function __construct(
        private readonly TransactionFactory $transactionFactory,
        private readonly TransactionPerformer $transactionPerformer,
        private readonly ExchangeRateUpdater $exchangeRateUpdater,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $sourceAccountId = $request->query->get('from');
        $targetAccountId = $request->query->get('to');
        $currency = $request->query->get('currency');
        $amount = $request->query->get('amount');

        if (is_null($sourceAccountId)) {
            return $this->createErrorResponse('No source account ID provided. Set \'from\' POST parameter.');
        }
        if (is_null($targetAccountId)) {
            return $this->createErrorResponse('No target account ID provided. Set \'to\' POST parameter.');
        }
        if (is_null($amount)) {
            return $this->createErrorResponse('No amount provided. Set \'amount\' POST parameter.');
        }
        if (is_null($currency)) {
            return $this->createErrorResponse('No currency provided. Set \'currency\' POST parameter.');
        }

        $this->exchangeRateUpdater->updateRatesIfNecessary();

        $connection = $this->entityManager->getConnection();
        $connection->setAutoCommit(false);
        $connection->beginTransaction();

        try {
            $transaction = $this->transactionFactory->create(
                (int) $sourceAccountId,
                (int) $targetAccountId,
                strtoupper((string) $currency),
                (int) $amount
            );

            $this->transactionPerformer->perform($transaction);
            $connection->commit();

            return new JsonResponse([
                'success' => true,
                'message' => 'Transaction done',
                'transaction' => [
                    'from' => [
                        'client' => [
                            'id' => $transaction->getSource()?->getClient()?->getId(),
                            'name' => $transaction->getSource()?->getClient()?->getName(),
                        ],
                        'account' => $transaction->getSource()?->getId(),
                        'currency' => $transaction->getSourceCurrency(),
                        'amount' => $transaction->getSourceAmount(),
                    ],
                    'to' => [
                        'client' => [
                            'id' => $transaction->getTarget()?->getClient()?->getId(),
                            'name' => $transaction->getTarget()?->getClient()?->getName(),
                        ],
                        'account' => $transaction->getTarget()?->getId(),
                        'currency' => $transaction->getTargetCurrency(),
                        'amount' => $transaction->getTargetAmount(),
                    ],
                ],
            ]);
        } catch (TransactionException $exception) {
            return $this->createErrorResponse($exception->getMessage());
        }
    }
}

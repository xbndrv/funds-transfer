<?php

namespace App\Controller;

use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/account-transactions', name: 'app_account_transactions')]
class AccountTransactionsListController extends AbstractController
{
    use ErrorTrait;
    public const DEFAULT_LIMIT = 50;

    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly TransactionRepository $transactionRepository
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $accountId = $request->query->get('account');
        $offset = $request->query->getInt('offset');
        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT);

        if (is_null($accountId)) {
            return $this->createErrorResponse('No account id provided. Set \'account\' GET parameter.');
        }

        $account = $this->accountRepository->find($accountId);
        if (is_null($account)) {
            return $this->createErrorResponse('Account #'.$accountId.' not found');
        }

        $total = $this->transactionRepository->countAccountTransactions($account);

        $list = [];
        foreach ($this->transactionRepository->findAccountTransactions($account, $limit, $offset) as $transaction) {
            $row = [
                'id' => $transaction->getId(),
                'date' => $transaction->getDate()->format('Y-m-d H:i:s'),
            ];
            $converted =
                $transaction->getSourceCurrency() != $transaction->getTargetCurrency()
                || $transaction->getSourceAmount() != $transaction->getTargetAmount();
            if ($transaction->getSource() === $account) {
                $row['account'] = $transaction->getTarget()?->getId();
                $row['amount'] = -1 * $transaction->getSourceAmount();
                if ($converted) {
                    $row['converted'] = [
                        'amount' => $transaction->getTargetAmount(),
                        'currency' => $transaction->getTargetCurrency(),
                    ];
                }
            } else {
                $row['account'] = $transaction->getSource()?->getId();
                $row['amount'] = $transaction->getTargetAmount();
                if ($converted) {
                    $row['converted'] = [
                        'amount' => $transaction->getSourceAmount(),
                        'currency' => $transaction->getSourceCurrency(),
                    ];
                }
            }
            $list[] = $row;
        }

        return new JsonResponse([
            'success' => true,
            'account' => [
                'id' => $account->getId(),
                'currency' => $account->getCurrency(),
            ],
            'client' => [
                'id' => $account->getClient()?->getId(),
                'name' => $account->getClient()?->getName(),
            ],
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
            'transactions' => $list,
        ]);
    }
}

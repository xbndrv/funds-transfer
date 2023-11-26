<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    public function countAccountTransactions(Account $account): int
    {
        $result = $this->createQueryBuilder('t')
            ->select('COUNT(t.id) AS number')
            ->where('t.source=:account OR t.target=:account')
            ->setParameter('account', $account)
            ->getQuery()
            ->getSingleScalarResult();
        if (!is_int($result)) {
            throw new \Exception('TransactionRepository::countAccountTransactions error');
        }

        return $result;
    }

    /**
     * @return Transaction[]
     */
    public function findAccountTransactions(Account $account, int $limit, int $offset = 0): array
    {
        /** @var array<Transaction> $result */
        $result = $this->createQueryBuilder('t')
            ->select('t')
            ->where('t.source=:account OR t.target=:account')
            ->setParameter('account', $account)
            ->orderBy('t.date', 'DESC')
            ->addOrderBy('t.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $result;
    }
}

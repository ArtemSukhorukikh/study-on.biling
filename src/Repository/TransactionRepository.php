<?php

namespace App\Repository;

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

    public function add(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transaction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findEndingForMail($userId): array
    {
        $connect = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT c.title, t.expires_at FROM transaction t
            INNER JOIN course c ON c.id = t.course_id
            WHERE t.type = 0 
            AND t.to_user_id = :user_id 
            AND t.expires_at::date = (now()::date + '1 day'::interval)
            ORDER BY t.created_at DESC
            ";
        $query = $connect->prepare($sql);
        $query = $query->executeQuery([
            'user_id' => $userId,
        ]);
        return $query->fetchAllAssociative();
    }

    public function forReportForMail(): array
    {
        $connect = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT c.title, c.type, count(t.id), sum(t.value)
            FROM transaction t
            JOIN course c on t.course_id = c.id
            WHERE t.created_at::date between (now()::date - '1 month'::interval) AND now()::date
            AND t.type = 0
            GROUP BY c.title, c.type
            ";
        $query = $connect->prepare($sql);
        $query = $query->executeQuery();
        return $query->fetchAllAssociative();
    }

//    /**
//     * @return Transaction[] Returns an array of Transaction objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Transaction
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

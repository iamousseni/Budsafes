<?php

namespace App\Repository;

use App\Entity\BusinessTraffic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method BusinessTraffic|null find($id, $lockMode = null, $lockVersion = null)
 * @method BusinessTraffic|null findOneBy(array $criteria, array $orderBy = null)
 * @method BusinessTraffic[]    findAll()
 * @method BusinessTraffic[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BusinessTrafficRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BusinessTraffic::class);
    }

    // /**
    //  * @return BusinessTraffic[] Returns an array of BusinessTraffic objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BusinessTraffic
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    public function orderdBusinessTrafficByMonth($budget_id){
      $entityManager = $this->getEntityManager();
      $entityManagerConfig = $entityManager->getConfiguration();
      $entityManagerConfig->addCustomDatetimeFunction('YEAR', 'DoctrineExtensions\Query\Mysql\Year');
      $entityManagerConfig->addCustomDatetimeFunction('MONTH', 'DoctrineExtensions\Query\Mysql\Month');
      $entityManagerConfig->addCustomDatetimeFunction('DAY', 'DoctrineExtensions\Query\Mysql\Day');

      $query = $entityManager->createQuery(
        "SELECT business_traffic, MONTH(business_traffic.added) AS month  FROM App\Entity\BusinessTraffic AS business_traffic
              WHERE business_traffic.budget = :budget 
              ORDER BY MONTH(business_traffic.added), business_traffic.added DESC"
      )->setParameter('budget', $budget_id);

      return $query->getResult();
    }

    public function deleteAllBusinessTrafficDataOfBudget($budget_id){
      $entityManager = $this->getEntityManager();
      $query = $entityManager->createQuery(
        "DELETE FROM App\Entity\BusinessTraffic AS business_traffic 
               WHERE business_traffic.budget = :budget_id"
      )->setParameter('budget_id', $budget_id);

      return $query->getResult();
    }

    public function findBusinessTrafficDataByType($type, $budget_id){
      $entityManager = $this->getEntityManager();
      $query = $entityManager->createQuery(
        "SELECT business_traffic FROM App\Entity\BusinessTraffic AS business_traffic
            INNER JOIN App\Entity\Category AS category WITH business_traffic.category = category.id 
            WHERE category.type = :type AND category.budget = :budget_id
            ORDER BY MONTH(business_traffic.added) DESC"
      )->setParameters([
        'type' => $type,
        'budget_id' => $budget_id
      ]);

      return $query->getResult();
    }

    public function findBusinessTrafficDataFromPeriod($dateFrom, $dateTo){
      $entityManager = $this->getEntityManager();
      $query = $entityManager->createQuery(
        "SELECT business_traffic FROM App\Entity\BusinessTraffic AS business_traffic
               WHERE business_traffic.added BETWEEN :dateFrom AND :dateTo
               ORDER BY MONTH(business_traffic.added) DESC"
      )->setParameters([
        ':dateFrom' => $dateFrom,
        ':dateTo' => $dateTo
      ]);

      return $query->getResult();
    }
}

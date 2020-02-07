<?php

namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Schedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Schedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Schedule[]    findAll()
 * @method Schedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    public function isExistScheduleCategory($category_id){
      $entityManager = $this->getEntityManager();
      $query = $entityManager->createQuery(
        "SELECT schedule FROM App\Entity\Schedule as schedule 
              WHERE schedule.category = :category_id"
      )->setParameter('category_id', $category_id);

      return count($query->getResult()) > 0 ? true : false;
    }

    public function findAllScheduleIncomesOfBudget($budget_id){
      $entityManager = $this->getEntityManager();
      $query = $entityManager->createQuery(
        "SELECT schedule FROM App\Entity\Schedule as schedule 
              INNER JOIN App\Entity\Category as category 
              WITH category.id = schedule.category
              WHERE category.type = 0 AND category.budget = :budget_id AND category.status = 1"
      )->setParameter('budget_id', $budget_id);

      return $query->getResult();
    }

  public function findAllScheduleOutcomesOfBudget($budget_id){
    $entityManager = $this->getEntityManager();
    $query = $entityManager->createQuery(
      "SELECT schedule FROM App\Entity\Schedule as schedule 
              INNER JOIN App\Entity\Category as category 
              WITH category.id = schedule.category
              WHERE category.type = 1 AND category.budget = :budget_id AND category.status = 1"
    )->setParameter('budget_id', $budget_id);

    return $query->getResult();
  }

  public function findAllScheduleSavesOfBudget($budget_id){
    $entityManager = $this->getEntityManager();
    $query = $entityManager->createQuery(
      "SELECT schedule FROM App\Entity\Schedule as schedule 
              INNER JOIN App\Entity\Category as category 
              WITH category.id = schedule.category
              WHERE category.type = 2 AND category.budget = :budget_id AND category.status = 1"
    )->setParameter('budget_id', $budget_id);

    return $query->getResult();
  }

    public function findIncomesScheduleByBudget($budget_id){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT schedule FROM App\Entity\Category as category
                INNER JOIN App\Entity\Schedule AS schedule
                WITH category.id = schedule.category
                WHERE category.type = 0 AND category.budget = :budget_id AND category.status = 1
                ORDER BY schedule.amount"
        )->setParameter(':budget_id', $budget_id);

        return $query->getResult();
    }

    public function findOutcomesScheduleByBudget($budget_id){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT schedule FROM App\Entity\Category as category
                INNER JOIN App\Entity\Schedule AS schedule
                WITH category.id = schedule.category
                WHERE category.type = 1 AND category.budget = :budget_id AND category.status = 1
                ORDER BY schedule.amount"
        )->setParameter(':budget_id', $budget_id);

        return $query->getResult();
    }

    public function findSavesScheduleByBudget($budget_id){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT schedule FROM App\Entity\Category as category
                INNER JOIN App\Entity\Schedule AS schedule
                WITH category.id = schedule.category
                WHERE category.type = 2 AND category.budget = :budget_id AND category.status = 1
                ORDER BY schedule.amount"
        )->setParameter(':budget_id', $budget_id);

        return $query->getResult();
    }

    // /**
    //  * @return Schedule[] Returns an array of Schedule objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Schedule
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

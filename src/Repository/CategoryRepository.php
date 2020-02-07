<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findIncomesCategoriesByBudget($budget_id){
      return $this->findBy(['type' => 0, 'budget' => $budget_id]);
    }

    public function findAllCategoriesBusinessTrafficByBudget($budget_id, $month){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT category FROM App\Entity\Category as category
                INNER JOIN App\Entity\BusinessTraffic AS business_traffic
                WITH category.id = business_traffic.category
                WHERE category.budget = :budget_id AND category.status = 1 AND MONTH(business_traffic.added) = :month
                ORDER BY category.type, business_traffic.added"
        )->setParameters([
            ':budget_id' => $budget_id,
            ':month' => $month
        ]);

        return $query->getResult();
    }


    public function findIncomesBusinessTrafficByBudget($budget_id, $month){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT category FROM App\Entity\Category as category
                INNER JOIN App\Entity\BusinessTraffic AS business_traffic
                WITH category.id = business_traffic.category
                WHERE category.type = 0 AND category.budget = :budget_id AND category.status = 1 AND MONTH(business_traffic.added) = :month
                ORDER BY business_traffic.added"
        )->setParameters([
            ':budget_id' => $budget_id,
            ':month' => $month
        ]);

        return $query->getResult();
    }

    public function getSumAmountIncomesBusinessTrafficByBudget($budget_id, $month){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT SUM(business_traffic.amount) AS total FROM App\Entity\Category as category
                INNER JOIN App\Entity\BusinessTraffic AS business_traffic
                WITH category.id = business_traffic.category
                WHERE category.type = 0 AND category.budget = :budget_id AND category.status = 1 AND MONTH(business_traffic.added) = :month
                ORDER BY business_traffic.added"
        )->setParameters([
            ':budget_id' => $budget_id,
            ':month' => $month
        ]);

        return $query->getResult();
    }

    public function findOutcomesBusinessTrafficByBudget($budget_id, $month){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT category FROM App\Entity\Category as category
                INNER JOIN App\Entity\BusinessTraffic AS business_traffic
                WITH category.id = business_traffic.category
                WHERE category.type = 1 AND category.budget = :budget_id AND category.status = 1 AND MONTH(business_traffic.added) = :month
                ORDER BY business_traffic.added"
        )->setParameters([
            ':budget_id' => $budget_id,
            ':month' => $month
        ]);

        return $query->getResult();
    }

    public function getSumAmountOutcomesBusinessTrafficByBudget($budget_id, $month){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT SUM(business_traffic.amount) AS total FROM App\Entity\Category as category
                INNER JOIN App\Entity\BusinessTraffic AS business_traffic
                WITH category.id = business_traffic.category
                WHERE category.type = 1 AND category.budget = :budget_id AND category.status = 1 AND MONTH(business_traffic.added) = :month
                ORDER BY business_traffic.added"
        )->setParameters([
            ':budget_id' => $budget_id,
            ':month' => $month
        ]);

        return $query->getResult();
    }

    public function findSavesBusinessTrafficByBudget($budget_id, $month){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT category FROM App\Entity\Category as category
                INNER JOIN App\Entity\BusinessTraffic AS business_traffic
                WITH category.id = business_traffic.category
                WHERE category.type = 2 AND category.budget = :budget_id AND category.status = 1 AND MONTH(business_traffic.added) = :month
                ORDER BY business_traffic.added"
        )->setParameters([
            ':budget_id' => $budget_id,
            ':month' => $month
        ]);

        return $query->getResult();
    }

    public function getSumAmountSavesBusinessTrafficByBudget($budget_id, $month){
        $entityManager = $this->getEntityManager();
        $query = $entityManager->createQuery(
            "SELECT SUM(business_traffic.amount) AS total FROM App\Entity\Category as category
                INNER JOIN App\Entity\BusinessTraffic AS business_traffic
                WITH category.id = business_traffic.category
                WHERE category.type = 2 AND category.budget = :budget_id AND category.status = 1 AND MONTH(business_traffic.added) = :month
                ORDER BY business_traffic.added"
        )->setParameters([
            ':budget_id' => $budget_id,
            ':month' => $month
        ]);

        return $query->getResult();
    }

    public function findOutcomesCategoriesByBudget($budget_id){
      return $this->findBy(['type' => 1, 'budget' => $budget_id]);
    }

    public function findSavesCategoriesByBudget($budget_id){
      return $this->findBy(['type' => 2, 'budget' => $budget_id]);
    }

  public function deleteAllCategoryDataOfBudget($budget_id){
    $entityManager = $this->getEntityManager();
    $query = $entityManager->createQuery(
      "DELETE FROM App\Entity\Category AS category 
               WHERE category.budget = :budget_id"
    )->setParameter('budget_id', $budget_id);

    return $query->getResult();
  }

    // /**
    //  * @return Category[] Returns an array of Category objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

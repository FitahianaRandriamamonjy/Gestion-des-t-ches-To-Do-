<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findByFilters(?string $status, ?string $priority, ?string $assignedTo, ?string $query = null, int $page = 1, int $limit = 10): array
    {
        $qb = $this->buildFilteredQuery($status, $priority, $assignedTo, $query)
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult(max(0, $page - 1) * $limit)
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function countByFilters(?string $status, ?string $priority, ?string $assignedTo, ?string $query = null): int
    {
        $qb = $this->buildFilteredQuery($status, $priority, $assignedTo, $query)
            ->select('COUNT(t.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function buildFilteredQuery(?string $status, ?string $priority, ?string $assignedTo, ?string $query = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.assignedTo', 'u')
            ->addSelect('u');

        if ($status) {
            $qb->andWhere('t.status = :status')->setParameter('status', $status);
        }

        if ($priority) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $priority);
        }

        if ($assignedTo) {
            $qb->andWhere('u.id = :assignedTo')->setParameter('assignedTo', $assignedTo);
        }

        if ($query) {
            $qb->andWhere('t.title LIKE :query OR t.description LIKE :query')
                ->setParameter('query', '%'.$query.'%');
        }

        return $qb;
    }

    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('t')
            ->select('t.status as status, COUNT(t.id) as total')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            $status = $row['status'] instanceof \App\Enum\TaskStatus ? $row['status']->value : $row['status'];
            $counts[$status] = (int) $row['total'];
        }

        return $counts;
    }
}

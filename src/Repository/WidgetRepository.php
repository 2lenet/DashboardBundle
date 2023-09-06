<?php

namespace Lle\DashboardBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Persistence\ManagerRegistry;
use Lle\DashboardBundle\Entity\Widget;

/**
 * @method Widget|null find($id, $lockMode = null, $lockVersion = null)
 * @method Widget|null findOneBy(array $criteria, array $orderBy = null)
 * @method Widget[]    findAll()
 * @method Widget[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WidgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Widget::class);
    }

    /**
     * Get the current user's widgets.
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getMyWidgets(UserInterface $user): QueryBuilder
    {
        $userId = method_exists($user, 'getId') ? $user->getId() : null;

        return $this->createQueryBuilder("w")
            ->select('w')
            ->where('w.user_id = :user_id')
            ->setParameter('user_id', $userId)
        ;
    }

    /**
     * Get user's widgets ordered by y
     */
    public function getWidgetsOrderedByY(UserInterface $user): array
    {
        $userId = method_exists($user, 'getId') ? $user->getId() : null;

        return $this->createQueryBuilder('w')
            ->where('w.user_id = :userId')
            ->setParameter('userId', $userId)
            ->addOrderBy('w.y', 'ASC')
            ->addOrderBy('w.x', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteMyWidgets(int $userId): void
    {
        $this->createQueryBuilder("w")
            ->delete()
            ->where("w.user_id = :user_id")
            ->setParameter("user_id", $userId)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param $user_id
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * Obtenir le widget le plus bas de la grille
     */
    public function getBottomWidget(int $userId): ?Widget
    {
        return $this->createQueryBuilder("w")
            ->where("w.user_id = :user_id")
            ->setParameter("user_id", $userId)
            ->addOrderBy("w.y", "DESC")
            ->addOrderBy("w.height", "DESC")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Delete the default dashboard (i.e. widgets with user_id = null)
     */
    public function deleteDefaultDashboard(): QueryBuilder
    {
        return $this->createQueryBuilder("w")
            ->delete()
            ->andWhere("w.user_id IS NULL");
    }

    /**
     * @param $userId the user
     *
     * Set user's widgets as default widgets
     */
    public function setDashboardAsDefault(int $userId): QueryBuilder
    {
        return $this->createQueryBuilder("w")
            ->update()
            ->set("w.user_id", "NULL")
            ->andWhere("w.user_id = :userId")
            ->setParameter("userId", $userId);
    }

    /**
     * Delete all the widgets (not the default ones)
     */
    public function deleteAllUserDashboards(): QueryBuilder
    {
        return $this->createQueryBuilder("w")
            ->delete()
            ->andWhere("w.user_id IS NOT NULL");
    }
}

<?php

namespace Lle\DashboardBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
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
    public function getMyWidgets(UserInterface $user)
    {
        $userId = method_exists($user, 'getId') ? $user->getId() : null;

        return $this->createQueryBuilder("w")
            ->select('w')
            ->where('w.user_id = :user_id')
            ->setParameter('user_id', $userId)
        ;
    }

    public function deleteMyWidgets($user_id)
    {
        $this->createQueryBuilder("w")
            ->delete()
            ->where("w.user_id = :user_id")
            ->setParameter("user_id", $user_id)
            ->getQuery()
            ->execute()
        ;
    }

    /**
     * @param $user_id
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * Obtenir le widget le plus bas de la grille
     */
    public function getBottomWidget($user_id)
    {
        return $this->createQueryBuilder("w")
            ->select("")
            ->where("w.user_id = :user_id")
            ->setParameter("user_id", $user_id)
            ->addOrderBy("w.y", "DESC")
            ->addOrderBy("w.height", "DESC")
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

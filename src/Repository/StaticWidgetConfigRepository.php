<?php

namespace Lle\DashboardBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Lle\DashboardBundle\Entity\StaticWidgetConfig;

/**
 * @extends ServiceEntityRepository<StaticWidgetConfig>
 */
class StaticWidgetConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StaticWidgetConfig::class);
    }

    public function findOneByUserAndWidget(string $userIdentifier, string $widgetKey): ?StaticWidgetConfig
    {
        return $this->findOneBy([
            'userIdentifier' => $userIdentifier,
            'widgetKey' => $widgetKey,
        ]);
    }

    public function save(StaticWidgetConfig $config): void
    {
        $this->getEntityManager()->persist($config);
        $this->getEntityManager()->flush();
    }

    public function remove(StaticWidgetConfig $config): void
    {
        $this->getEntityManager()->remove($config);
        $this->getEntityManager()->flush();
    }
}

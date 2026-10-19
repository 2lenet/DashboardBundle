<?php

namespace Lle\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Entity\Widget;
use Lle\DashboardBundle\Repository\WidgetRepository;
use Lle\DashboardBundle\Service\WidgetProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WidgetProviderTest extends TestCase
{
    private const USER_ID = 42;

    public function testSetDefaultWidgetsForUserCopiesTheDefaultLayoutThroughTheEntityMapping(): void
    {
        $defaultWidget = (new Widget())
            ->setX(2)
            ->setY(3)
            ->setWidth(4)
            ->setHeight(5)
            ->setType('commande_en_cours')
            ->setConfig(['filter' => 'today'])
            ->setTitle('Default title');

        $repository = $this->createMock(WidgetRepository::class);
        $repository->expects(self::once())
            ->method('findBy')
            ->with(['userId' => null])
            ->willReturn([$defaultWidget]);

        $persisted = [];
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Widget::class)->willReturn($repository);
        $em->expects(self::never())->method('getConnection');
        $em->method('persist')->willReturnCallback(static function (object $entity) use (&$persisted): void {
            $persisted[] = $entity;
        });
        $em->expects(self::once())->method('flush');

        $provider = new WidgetProvider($em, $this->createMock(TokenStorageInterface::class), []);
        $provider->setDefaultWidgetsForUser(self::USER_ID);

        $expected = (new Widget())
            ->setX(2)
            ->setY(3)
            ->setWidth(4)
            ->setHeight(5)
            ->setType('commande_en_cours')
            ->setUserId(self::USER_ID);

        self::assertEquals([$expected], $persisted);
        self::assertNotSame($defaultWidget, $persisted[0]);
        self::assertNull($defaultWidget->getUserId());
    }

    public function testSetDefaultWidgetsForUserIgnoresAnEmptyUserId(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::never())->method('getRepository');
        $em->expects(self::never())->method('flush');

        $provider = new WidgetProvider($em, $this->createMock(TokenStorageInterface::class), []);
        $provider->setDefaultWidgetsForUser(0);
    }
}

<?php

namespace Lle\Tests\Services;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Entity\Widget;
use Lle\DashboardBundle\Service\WidgetCompacterService;
use PHPUnit\Framework\TestCase;

class WidgetCompacterServiceTest extends TestCase
{
    public function testcompactY(): void
    {
        $widgets = [
            (new Widget())->setX(0)->setY(2)->setWidth(8)->setHeight(2),
            (new Widget())->setX(8)->setY(2)->setWidth(2)->setHeight(9),
            (new Widget())->setX(0)->setY(4)->setWidth(4)->setHeight(4),
            (new Widget())->setX(4)->setY(4)->setWidth(4)->setHeight(4),
            (new Widget())->setX(0)->setY(8)->setWidth(8)->setHeight(3),
            (new Widget())->setX(0)->setY(14)->setWidth(10)->setHeight(4),
            (new Widget())->setX(0)->setY(19)->setWidth(5)->setHeight(3),
            (new Widget())->setX(5)->setY(19)->setWidth(5)->setHeight(3),
        ];
        $expectedWidgets = [
            (new Widget())->setX(0)->setY(0)->setWidth(8)->setHeight(2),
            (new Widget())->setX(8)->setY(0)->setWidth(2)->setHeight(9),
            (new Widget())->setX(0)->setY(2)->setWidth(4)->setHeight(4),
            (new Widget())->setX(4)->setY(2)->setWidth(4)->setHeight(4),
            (new Widget())->setX(0)->setY(6)->setWidth(8)->setHeight(3),
            (new Widget())->setX(0)->setY(9)->setWidth(10)->setHeight(4),
            (new Widget())->setX(0)->setY(13)->setWidth(5)->setHeight(3),
            (new Widget())->setX(5)->setY(13)->setWidth(5)->setHeight(3),
        ];

        $widgetCompacterService = new WidgetCompacterService();
        $widgetCompacterService->compactY($widgets);

        $this->assertEquals($expectedWidgets, $widgets);
    }
}

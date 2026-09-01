<?php

namespace Lle\Tests\Services;

use Lle\DashboardBundle\Contracts\StaticTabProviderInterface;
use Lle\DashboardBundle\Contracts\StaticWidgetProviderInterface;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Dto\StaticTab;
use Lle\DashboardBundle\Service\StaticDashboardService;
use Lle\DashboardBundle\Widgets\AbstractWidget;
use PHPUnit\Framework\TestCase;

class StaticDashboardServiceTest extends TestCase
{
    public function testGetVisibleWidgetsKeepsOnlyGrantedWidgets(): void
    {
        $granted = $this->createWidget(true);
        $denied = $this->createWidget(false);
        $service = new StaticDashboardService($this->createProvider([
            'granted' => $granted,
            'denied' => $denied,
        ]));

        $this->assertSame(['granted' => $granted], $service->getVisibleWidgets());
    }

    public function testGetWidgetReturnsNullOnUnknownIndex(): void
    {
        $service = new StaticDashboardService($this->createProvider([]));

        $this->assertNull($service->getWidget('unknown'));
    }

    public function testIsGrantedReflectsWidgetSupports(): void
    {
        $service = new StaticDashboardService($this->createProvider([]));

        $this->assertTrue($service->isGranted($this->createWidget(true)));
        $this->assertFalse($service->isGranted($this->createWidget(false)));
    }

    public function testIsTabbedIsFalseForAPlainProvider(): void
    {
        $service = new StaticDashboardService($this->createProvider([]));

        $this->assertFalse($service->isTabbed());
        $this->assertSame([], $service->getVisibleTabs());
    }

    public function testGetVisibleTabsGroupsGrantedWidgetsInDeclarationOrder(): void
    {
        $composerWidget = $this->createWidget(true);
        $dockerWidget = $this->createWidget(true);
        $service = new StaticDashboardService($this->createTabbedProvider(
            [
                'component_risk' => $composerWidget,
                'deployed_service' => $dockerWidget,
            ],
            [
                new StaticTab('docker', 'dashboard.tab.docker', ['deployed_service']),
                new StaticTab('composer', 'dashboard.tab.composer', ['component_risk']),
            ],
        ));

        $tabs = $service->getVisibleTabs();

        $this->assertTrue($service->isTabbed());
        $this->assertCount(2, $tabs);
        $this->assertSame('docker', $tabs[0]['tab']->getKey());
        $this->assertSame(['deployed_service' => $dockerWidget], $tabs[0]['widgets']);
        $this->assertSame('composer', $tabs[1]['tab']->getKey());
        $this->assertSame(['component_risk' => $composerWidget], $tabs[1]['widgets']);
    }

    public function testGetVisibleTabsDropsTabsWithoutAnyGrantedWidget(): void
    {
        $granted = $this->createWidget(true);
        $service = new StaticDashboardService($this->createTabbedProvider(
            [
                'granted' => $granted,
                'denied' => $this->createWidget(false),
            ],
            [
                new StaticTab('empty', 'dashboard.tab.empty', ['denied']),
                new StaticTab('filled', 'dashboard.tab.filled', ['granted']),
            ],
        ));

        $tabs = $service->getVisibleTabs();

        $this->assertCount(1, $tabs);
        $this->assertSame('filled', $tabs[0]['tab']->getKey());
    }

    public function testGetVisibleTabsThrowsOnUnknownWidgetKey(): void
    {
        $service = new StaticDashboardService($this->createTabbedProvider(
            ['known' => $this->createWidget(true)],
            [new StaticTab('tab', 'dashboard.tab.tab', ['known', 'typo'])],
        ));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('typo');

        $service->getVisibleTabs();
    }

    public function testGetVisibleTabsThrowsOnWidgetAssignedToNoTab(): void
    {
        $service = new StaticDashboardService($this->createTabbedProvider(
            [
                'in_a_tab' => $this->createWidget(true),
                'orphan' => $this->createWidget(true),
            ],
            [new StaticTab('tab', 'dashboard.tab.tab', ['in_a_tab'])],
        ));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('orphan');

        $service->getVisibleTabs();
    }

    public function testGetVisibleTabsThrowsOnDuplicateTabKey(): void
    {
        $service = new StaticDashboardService($this->createTabbedProvider(
            ['widget' => $this->createWidget(true)],
            [
                new StaticTab('duplicated', 'dashboard.tab.first', ['widget']),
                new StaticTab('duplicated', 'dashboard.tab.second', ['widget']),
            ],
        ));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('duplicated');

        $service->getVisibleTabs();
    }

    public function testThrowsWhenNoProviderIsConfigured(): void
    {
        $service = new StaticDashboardService(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('lle_dashboard.static_widget_provider');

        $service->getVisibleWidgets();
    }

    public function testRenderWidgetUsesTheStaticRenderingOfTheWidget(): void
    {
        $service = new StaticDashboardService($this->createProvider([]));
        $widget = new class extends AbstractWidget {
            public function render(): string
            {
                return 'dynamic';
            }

            public function renderStatic(): string
            {
                return 'static';
            }
        };

        $this->assertSame('static', $service->renderWidget($widget));
    }

    public function testRenderWidgetFallsBackToRenderForAPlainWidgetType(): void
    {
        $service = new StaticDashboardService($this->createProvider([]));
        $widget = $this->createMock(WidgetTypeInterface::class);
        $widget->method('render')->willReturn('dynamic');

        $this->assertSame('dynamic', $service->renderWidget($widget));
    }

    private function createWidget(bool $granted): WidgetTypeInterface
    {
        $widget = $this->createMock(WidgetTypeInterface::class);
        $widget->method('supports')->willReturn($granted);

        return $widget;
    }

    /**
     * @param array<string, WidgetTypeInterface> $widgets
     */
    private function createProvider(array $widgets): StaticWidgetProviderInterface
    {
        return new class ($widgets) implements StaticWidgetProviderInterface {
            /**
             * @param array<string, WidgetTypeInterface> $widgets
             */
            public function __construct(private array $widgets)
            {
            }

            public function getMyWidgets(): array
            {
                return $this->widgets;
            }

            public function getWidget(string $index): ?WidgetTypeInterface
            {
                return $this->widgets[$index] ?? null;
            }
        };
    }

    /**
     * @param array<string, WidgetTypeInterface> $widgets
     * @param list<StaticTab> $tabs
     */
    private function createTabbedProvider(array $widgets, array $tabs): StaticTabProviderInterface
    {
        return new class ($widgets, $tabs) implements StaticTabProviderInterface {
            /**
             * @param array<string, WidgetTypeInterface> $widgets
             * @param list<StaticTab> $tabs
             */
            public function __construct(private array $widgets, private array $tabs)
            {
            }

            public function getMyWidgets(): array
            {
                return $this->widgets;
            }

            public function getWidget(string $index): ?WidgetTypeInterface
            {
                return $this->widgets[$index] ?? null;
            }

            public function getTabs(): array
            {
                return $this->tabs;
            }
        };
    }
}

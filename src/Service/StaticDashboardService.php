<?php

namespace Lle\DashboardBundle\Service;

use Lle\DashboardBundle\Contracts\StaticTabProviderInterface;
use Lle\DashboardBundle\Contracts\StaticWidgetProviderInterface;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Dto\StaticTab;
use Lle\DashboardBundle\Widgets\AbstractWidget;

/**
 * Reads the static dashboard layout from the application's provider, and hides everything
 * the current user is not granted: a widget whose supports() returns false is never listed
 * nor rendered, and a tab left without any granted widget is not displayed at all.
 */
class StaticDashboardService
{
    private const string MISSING_PROVIDER_MESSAGE = 'The static dashboard requires a widget provider. '
        . 'Set "lle_dashboard.static_widget_provider" to the FQCN of your %s implementation.';

    public function __construct(
        protected ?StaticWidgetProviderInterface $provider = null,
    ) {
    }

    public function isTabbed(): bool
    {
        return $this->getProvider() instanceof StaticTabProviderInterface;
    }

    /**
     * Widgets of a dashboard without tabs.
     *
     * @return array<string, WidgetTypeInterface>
     */
    public function getVisibleWidgets(): array
    {
        return array_filter(
            $this->getProvider()->getMyWidgets(),
            fn (WidgetTypeInterface $widget): bool => $this->isGranted($widget),
        );
    }

    /**
     * Tabs of a tabbed dashboard, in declaration order, without the ones the user cannot see.
     * An empty list means the dashboard has no tab at all.
     *
     * @return list<array{tab: StaticTab, widgets: array<string, WidgetTypeInterface>}>
     */
    public function getVisibleTabs(): array
    {
        $provider = $this->getProvider();
        if (!$provider instanceof StaticTabProviderInterface) {
            return [];
        }

        $widgets = $provider->getMyWidgets();
        $tabs = $provider->getTabs();

        $this->assertTabKeysAreUnique($tabs);
        $this->assertEveryWidgetIsAssignedToATab($widgets, $tabs);

        $visibleTabs = [];
        foreach ($tabs as $tab) {
            $tabWidgets = [];
            foreach ($tab->getWidgetKeys() as $widgetKey) {
                $widget = $widgets[$widgetKey] ?? null;
                if (!$widget instanceof WidgetTypeInterface) {
                    throw new \LogicException(sprintf(
                        'Tab "%s" references the widget "%s", which is not returned by getMyWidgets().',
                        $tab->getKey(),
                        $widgetKey,
                    ));
                }

                if ($this->isGranted($widget)) {
                    $tabWidgets[$widgetKey] = $widget;
                }
            }

            if ($tabWidgets) {
                $visibleTabs[] = ['tab' => $tab, 'widgets' => $tabWidgets];
            }
        }

        return $visibleTabs;
    }

    public function getWidget(string $index): ?WidgetTypeInterface
    {
        return $this->getProvider()->getWidget($index);
    }

    /**
     * Renders a widget for the static dashboard: renderStatic() when the widget provides it,
     * render() for a widget implementing WidgetTypeInterface without extending AbstractWidget.
     */
    public function renderWidget(WidgetTypeInterface $widget): string
    {
        if ($widget instanceof AbstractWidget) {
            return $widget->renderStatic();
        }

        return (string) $widget->render();
    }

    public function isGranted(WidgetTypeInterface $widget): bool
    {
        return (bool) $widget->supports();
    }

    /**
     * @param array<string, WidgetTypeInterface> $widgets
     * @param list<StaticTab> $tabs
     */
    private function assertEveryWidgetIsAssignedToATab(array $widgets, array $tabs): void
    {
        $assignedKeys = [];
        foreach ($tabs as $tab) {
            $assignedKeys = array_merge($assignedKeys, $tab->getWidgetKeys());
        }

        $orphanKeys = array_diff(array_keys($widgets), $assignedKeys);
        if ($orphanKeys) {
            throw new \LogicException(sprintf(
                'The widgets "%s" are assigned to no tab: a tabbed static dashboard cannot display '
                . 'widgets outside of its tabs, add them to a tab or remove them from getMyWidgets().',
                implode('", "', $orphanKeys),
            ));
        }
    }

    /**
     * @param list<StaticTab> $tabs
     */
    private function assertTabKeysAreUnique(array $tabs): void
    {
        $keys = array_map(static fn (StaticTab $tab): string => $tab->getKey(), $tabs);
        $duplicatedKeys = array_unique(array_diff_assoc($keys, array_unique($keys)));

        if ($duplicatedKeys) {
            throw new \LogicException(sprintf(
                'The tab keys "%s" are declared more than once, tab keys must be unique.',
                implode('", "', $duplicatedKeys),
            ));
        }
    }

    private function getProvider(): StaticWidgetProviderInterface
    {
        if (!$this->provider instanceof StaticWidgetProviderInterface) {
            throw new \LogicException(sprintf(
                self::MISSING_PROVIDER_MESSAGE,
                StaticWidgetProviderInterface::class,
            ));
        }

        return $this->provider;
    }
}

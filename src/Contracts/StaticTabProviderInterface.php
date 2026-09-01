<?php

namespace Lle\DashboardBundle\Contracts;

use Lle\DashboardBundle\Dto\StaticTab;

/**
 * Implement this instead of StaticWidgetProviderInterface to split the static dashboard into tabs.
 *
 * Every widget returned by getMyWidgets() must be assigned to exactly one tab: a tabbed dashboard
 * cannot also display widgets outside of its tabs.
 */
interface StaticTabProviderInterface extends StaticWidgetProviderInterface
{
    /**
     * @return list<StaticTab>
     */
    public function getTabs(): array;
}

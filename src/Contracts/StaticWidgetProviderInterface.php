<?php

namespace Lle\DashboardBundle\Contracts;

interface StaticWidgetProviderInterface
{
    public function getMyWidgets(): array;

    public function getWidget(string $index): ?WidgetTypeInterface;
}

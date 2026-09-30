<?php

namespace Lle\Tests\Fixtures;

use Lle\DashboardBundle\Widgets\AbstractWidget;

class FilterableWidget extends AbstractWidget
{
    public function getConfigFormType(): ?string
    {
        return FilterableWidgetType::class;
    }
}

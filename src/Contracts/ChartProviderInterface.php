<?php

namespace Lle\DashboardBundle\Contracts;

use Symfony\UX\Chartjs\Model\Chart;

interface ChartProviderInterface
{
    public function getChart(string $chartKey): Chart;

    public function getChartList(): array;
}

<?php

namespace Lle\DashboardBundle\Contracts;

interface DataProviderInterface
{
    public function getData(string $dataKey): array;

    public function getDataConf(): array;
}

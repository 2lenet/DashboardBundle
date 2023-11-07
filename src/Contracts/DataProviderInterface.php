<?php

namespace Lle\DashboardBundle\Contracts;

interface DataProviderInterface
{
    public function getData(string $valueSpec, string $groupSpec, ?int $number): array;

    public function getDataConf(): array;
}

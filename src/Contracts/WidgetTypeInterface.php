<?php

namespace Lle\DashboardBundle\Contracts;

use Lle\DashboardBundle\Entity\Widget;

interface WidgetTypeInterface
{
    public function getId(): ?int;

    public function render(): ?string;

    public function getHeight(): ?int;

    public function getWidth(): ?int;

    public function getX(): ?int;

    public function getY(): ?int;

    public function getType(): ?string;

    public function getName(): ?string;

    public function getTitle(): ?string;

    public function setParams(Widget $widget): WidgetTypeInterface;

    public function supports(): ?bool;

    public function supportsAjax(): ?bool;

    public function getCacheKey(): ?string;

    public function getCacheTimeout(): ?int;
}

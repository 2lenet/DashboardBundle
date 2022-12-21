<?php

namespace Lle\DashboardBundle\Service;

class WidgetCompacterService
{
    public function compactY(array $widgets): void
    {
        $yMin = 0;

        foreach ($widgets as $index => $widget) {
            if ($widget->getY() > $yMin) {
                $widgetsToShift = array_slice($widgets, $index);
                $offset = $widget->getY() - $yMin;
                $this->shiftWidgetsY($widgetsToShift, $offset);
            }

            $yMin = $widget->getY() + $widget->getHeight();
        }
    }

    private function shiftWidgetsY(array $widgets, int $offset): void
    {
        foreach ($widgets as $widget) {
            $widget->setY($widget->getY() - $offset);
        }
    }
}

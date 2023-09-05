<?php

namespace Lle\DashboardBundle\Contracts;

use Lle\DashboardBundle\Entity\Widget;

interface WidgetTypeInterface
{
    /**
     * @return mixed get unique ID
     */
    public function getId(): mixed;

    /**
     * @return string return widget HTML source
     */
    public function render(): string;

    /**
     * @return integer returns widget height
     */
    public function getHeight(): int;

    /**
     * @return integer returns widget width
     */
    public function getWidth(): int;

    /**
     * @return integer returns widget X position
     */
    public function getX(): int;

    /**
     * @return integer returns widget Y position
     */
    public function getY(): int;
    
    /**
     * @return string returns widget type
     */
    public function getType(): string;
    
    /**
     * @return string returns widget name
     */
    public function getName(): string;

    /**
     * @return string returns widget title
     */
    public function getTitle(): string;
    
    /**
     * @param Widget $widget
     */
    public function setParams(Widget $widget): WidgetTypeInterface;

    /**
     * @return bool
     *
     * Is the widget supported ?
     */
    public function supports(): bool;

    /**
     * @return bool
     *
     * Should the widget be asynchronously loaded ?
     */
    public function supportsAjax(): bool;

    /**
     * @return string
     *
     * Returns the widget's cache key. It should be based on widget's properties.
     */
    public function getCacheKey(): string;

    /**
     * @return int
     *
     * In seconds, how long should the cache last.
     */
    public function getCacheTimeout(): int;
}

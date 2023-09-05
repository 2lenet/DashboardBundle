<?php

namespace Lle\DashboardBundle\Contracts;

interface WidgetTypeInterface
{
    /**
     * @return mixed get unique ID
     */
    public function getId();

    /**
     * @return string return widget HTML source
     */
    public function render();

    /**
     * @return integer returns widget height
     */
    public function getHeight();

    /**
     * @return integer returns widget width
     */
    public function getWidth();

    /**
     * @return integer returns widget X position
     */
    public function getX();

    /**
     * @return integer returns widget Y position
     */
    public function getY();
    
    /**
     * @return string returns widget type
     */
    public function getType();
    
    /**
     * @return string returns widget name
     */
    public function getName();

    /**
     * @return string returns widget title
     */
    public function getTitle();
    
    /**
     * @param \Lle\DashboardBundle\Entity\Widget $widget
     */
    public function setParams(\Lle\DashboardBundle\Entity\Widget $widget);

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

<?php

namespace Lle\DashboardBundle\Contracts;

use Symfony\Component\HttpFoundation\Response;

/**
 * Description of WidgetInterface
 *
 * @author Tomasz Kuśka <tomasz.kuska@gmail.com>
 */
interface WidgetTypeInterface
{
    /**
     * @return string return widgets HTML source
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
     * @return json schema for config
     */
    public function getJsonSchema();

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
    public function support(): bool;

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

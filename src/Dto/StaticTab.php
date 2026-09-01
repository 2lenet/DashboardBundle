<?php

namespace Lle\DashboardBundle\Dto;

/**
 * One tab of a tabbed static dashboard.
 */
class StaticTab
{
    /**
     * @param string $key Stable identifier of the tab, used in the DOM and in the url fragment
     * @param string $label Title of the tab, translated with the default domain
     * @param list<string> $widgetKeys Keys of the widgets to display, as returned by the provider
     * @param string|null $icon Css classes of an icon displayed before the label, e.g. "fa fa-docker"
     * @param string|null $cssClass Css classes added to the tab itself, e.g. "text-danger"
     */
    public function __construct(
        protected string $key,
        protected string $label,
        protected array $widgetKeys,
        protected ?string $icon = null,
        protected ?string $cssClass = null,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return list<string>
     */
    public function getWidgetKeys(): array
    {
        return $this->widgetKeys;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getCssClass(): ?string
    {
        return $this->cssClass;
    }
}

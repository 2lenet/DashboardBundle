<?php

namespace Lle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Lle\DashboardBundle\Repository\StaticWidgetConfigRepository;

/**
 * Stores the values as posted: they are typed again by the widget's config form at read time.
 */
#[ORM\Table(name: 'lle_dashboard_static_widget_config')]
#[ORM\UniqueConstraint(name: 'uniq_lle_dashboard_static_widget_config_user_widget', columns: ['user_identifier', 'widget_key'])]
#[ORM\Entity(repositoryClass: StaticWidgetConfigRepository::class)]
class StaticWidgetConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    // not an id: UserInterface does not guarantee one
    #[ORM\Column(name: 'user_identifier', type: 'string', length: 180)]
    protected string $userIdentifier;

    #[ORM\Column(name: 'widget_key', type: 'string', length: 100)]
    protected string $widgetKey;

    #[ORM\Column(name: 'config', type: 'json')]
    protected array $config = [];

    public function __construct(string $userIdentifier, string $widgetKey)
    {
        $this->userIdentifier = $userIdentifier;
        $this->widgetKey = $widgetKey;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function getWidgetKey(): string
    {
        return $this->widgetKey;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }
}

<?php

namespace Lle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Repository\WidgetRepository;

#[ORM\Table(name: 'widgets')]
#[ORM\Entity(repositoryClass: WidgetRepository::class)]
class Widget
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\Column(name: 'x', type: 'integer', nullable: true)]
    protected ?int $x = 0;

    #[ORM\Column(name: 'y', type: 'integer', nullable: true)]
    protected ?int $y = 0;

    #[ORM\Column(name: 'width', type: 'integer', nullable: true)]
    protected ?int $width = null;

    #[ORM\Column(name: 'height', type: 'integer', nullable: true)]
    protected ?int $height = null;

    #[ORM\Column(name: 'type', type: 'string', length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(name: 'user_id', type: 'integer', nullable: true)]
    private ?int $userId = null;

    #[ORM\Column(name: 'config', type: 'json', nullable: true)]
    private ?array $config = null;

    #[ORM\Column(name: 'title', type: 'string', nullable: true)]
    private ?string $title = null;

    public function importConfig(WidgetTypeInterface $widgetType): self
    {
        $this->type = $widgetType->getType();
        $this->width = $widgetType->getWidth();
        $this->height = $widgetType->getHeight();
        $this->x = $widgetType->getX();
        $this->y = $widgetType->getY();

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }
    
    public function getX(): ?int
    {
        return $this->x;
    }

    public function setX(?int $x): self
    {
        $this->x = $x;

        return $this;
    }

    public function getY(): ?int
    {
        return $this->y;
    }

    public function setY(?int $y): self
    {
        $this->y = $y;

        return $this;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function setWidth(?int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function setHeight(?int $height): self
    {
        $this->height = $height;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getConfig(): ?array
    {
        return $this->config;
    }

    public function setConfig(?array $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }
}

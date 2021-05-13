<?php

namespace Lle\DashboardBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Lle\DashboardBundle\Contracts\WidgetTypeInterface;

/**
 * @ORM\Table(name="widgets")
 * @ORM\Entity(repositoryClass="Lle\DashboardBundle\Repository\WidgetRepository")
 */

class Widget
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="x", type="integer", nullable=true)
     */
    protected $x = 0;

    /**
     * @ORM\Column(name="y", type="integer", nullable=true)
     */
    protected $y = 0;

    /**
     * @ORM\Column(name="width", type="integer", nullable=true)
     */
    protected $width;

    /**
     * @ORM\Column(name="height", type="integer", nullable=true)
     */
    protected $height;
    
    /**
     * @ORM\Column(name="type", type="string", length=100, nullable=true)
     */
    private $type;
    
    /**
     * @ORM\Column(name="user_id", type="integer", nullable=true)
     */
    private $user_id;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $config;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

    /**
     * Import config.
     */
    public function importConfig(WidgetTypeInterface $widgetType)
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
        return $this->user_id;
    }

    public function setUserId(?int $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function setConfig($config): self
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

<?php

namespace Lle\DashboardBundle\Widgets;

use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Entity\Widget;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

abstract class AbstractWidget implements WidgetTypeInterface
{
    protected int $id;

    // x position
    protected int $x = 0;

    // y position
    protected int $y = 0;

    // widget width
    protected int $width = 4;

    // widget height
    protected int $height = 5;

    // json config
    protected ?array $config = null;

    // widget title
    protected ?string $title = null;

    protected Security $security;

    protected Environment $twig;

    protected FormFactoryInterface $formFactory;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): self
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @inheritdoc
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @inheritdoc
     */
    public function getX(): ?int
    {
        return $this->x;
    }

    /**
     * @inheritdoc
     */
    public function getY(): ?int
    {
        return $this->y;
    }

    /**
     * @inheritdoc
     */
    public function render(): string
    {
        return "You should implement the render method in " . get_class($this);
    }

    /**
     * @inheritdoc
     */
    public function getType(): string
    {
        return str_replace("\\", "_", get_class($this)) . "_widget";
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return get_class($this);
    }

    /**
     * Returns the configuration, can be a specific key or the entire configuration
     * @param $key the name of the configuration field
     * @param $default default value when the key doesn't exist
     */
    public function getConfig(mixed $key = null, mixed $default = null): mixed
    {
        if ($key) {
            return $this->config[$key] ?? $default;
        }

        return $this->config;
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @inheritdoc
     */
    public function setParams(Widget $widget): self
    {
        $this->id = $widget->getId();
        $this->x = $widget->getX();
        $this->y = $widget->getY();
        $this->width = $widget->getWidth();
        $this->height = $widget->getHeight();
        $this->config = $widget->getConfig();
        $this->title = $widget->getTitle();

        return $this;
    }

    public function __toString(): string
    {
        return $this->getName() . "(" . $this->getType() . ")";
    }

    public function getConfigForm(): ?FormInterface
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function supports(): bool
    {
        $widgetName = (new \ReflectionClass($this))->getShortName();
        $widgetName = preg_replace("/(?<!\ )[A-Z]/", "_$0", $widgetName);
        $widgetName = strtoupper($widgetName);
        $role = "ROLE_DASHBOARD" . $widgetName;

        return $this->security->isGranted($role);
    }

    /**
     * @inheritdoc
     */
    public function supportsAjax(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getCacheKey(): string
    {
        $uniqueKey = json_encode(array($this->config, $this->width, $this->height, $this->title, $this->x, $this->y));

        return $this->getId() . "_" . md5($uniqueKey);
    }

    /**
     * @inheritdoc
     */
    public function getCacheTimeout(): int
    {
        return 300;
    }

    /**
     * Helper functions
     */

    public function createForm(string $type, mixed $data = null, array $options = []): FormInterface
    {
        return $this->formFactory
            ->createNamed("form_widget_" . $this->getId(), $type, $data, $options);
    }

    public function twig(string $template, array $context = []): string
    {
        return $this->twig->render($template, array_merge(
            [
                "widget" => $this,
            ],
            $context
        ));
    }

    /**
     * Services injection
     */

    /**
     * @required
     */
    public function setSecurity(Security $security): self
    {
        $this->security = $security;

        return $this;
    }

    /**
     * @required
     */
    public function setTwig(Environment $twig): self
    {
        $this->twig = $twig;

        return $this;
    }

    /**
     * @required
     */
    public function setFormFactory(FormFactoryInterface $formFactory): self
    {
        $this->formFactory = $formFactory;

        return $this;
    }
}

<?php

namespace Lle\DashboardBundle\Widgets;

use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Entity\Widget;
use Lle\DashboardBundle\Form\Type\JsonType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

abstract class AbstractWidget implements WidgetTypeInterface
{
    /**
     * @var int
     */
    private int $id;

    /**
     * @var int x position
     */
    protected int $x = 0;

    /**
     * @var int y position
     */
    protected int $y = 0;

    /**
     * @var int widget width
     */
    protected int $width = 4;

    /**
     * @var int widget height
     */
    protected int $height = 5;

    /**
     * @var array json config
     */
    private ?array $config = null;

    /**
     * @var string widget title
     */
    private ?string $title = null;

    /**
     * @var Security
     */
    private AuthorizationCheckerInterface $security;

    private Environment $twig;

    private FormFactoryInterface $formFactory;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @inheritdoc
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @inheritdoc
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @inheritdoc
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return "You should implement the render method in " . get_class($this);
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return str_replace("\\", "_", get_class($this)) . "_widget";
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return get_class($this);
    }

    /**
     * Returns the configuration, can be a specific key or the entire configuration
     * @param $key the name of the configuration field
     * @param $default default value when the key doesn't exist
     * @return string a string representing the entire config or the value of the key
     */
    public function getConfig($key = null, $default = null)
    {
        if ($key) {
            return $this->config[$key] ?? $default;
        }

        return $this->config;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getJsonSchema()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function setParams(Widget $widget)
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

    public function __toString()
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

    public function createForm(string $type, $data = null, array $options = []): FormInterface
    {
        return $this->formFactory
            ->create($type, $data, $options);
    }

    public function twig($template, array $context = []): string
    {
        return $this->twig
            ->render($template, array_merge([
                "widget" => $this,
            ], $context));
    }

    /**
     * Services injection
     */

    /**
     * @required
     */
    public function setSecurity(AuthorizationCheckerInterface $security): self
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

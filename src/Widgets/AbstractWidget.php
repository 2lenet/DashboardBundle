<?php

namespace Lle\DashboardBundle\Widgets;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\ItemInterface;
use Twig_Environment as Environment;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

use Lle\DashboardBundle\Form\Type\JsonType;
use Lle\DashboardBundle\Entity\Widget;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;

/**
 * Description of CalendarWidget.
 *
 */
abstract class AbstractWidget implements WidgetTypeInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int x position 
     */
    protected $x = 0;

    /**
     * @var int y position
     */
    protected $y = 0;

    /**
     * @var int widget width
     */
    protected $width = 4;

    /**
     * @var int widget height
     */
    protected $height = 5;

    /**
     * @var string json config
     */
    private $config;

    /**
     * @var string widget title
     */
    private $title;

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
        return 'You should implement the render method in '. get_class($this);
    }

    /**
     * @inheritdoc
     */
    public function getType()
    {
        return str_replace('\\','_', get_class($this)).'_widget';
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
     * @param $defaultValue default value when the key doesn't exist
     * @return string a string representing the entire config or the value of the key
     */
    public function getConfig($key=null, $defaultValue=null)
    {
        $config = json_decode($this->config, true);
        
        if ($key) {
            return $config[$key] ?? $defaultValue;
        }
        return $config;
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
        return $this->getName().'('.$this->getType().')';
    }

    public function getConfigForm()
    {
        if ($this->getJsonSchema()) {
            $formFactory = Forms::createFormFactory();
            $form = $formFactory->create()
                ->add('Configuration', JsonType::class, array('schema' => $this->getJsonSchema(), 'theme' => 'bootstrap3'))
                ->add('json_form_'.$this->getId(), HiddenType::class)
            ;
            
            return $form->createView();
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function support(): bool
    {
        return true;
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
        return $this->getId()."_".md5($uniqueKey);
    }

    /**
     * @inheritdoc
     */
    public function getCacheTimeout(): int
    {
        return 300;
    }
    
}

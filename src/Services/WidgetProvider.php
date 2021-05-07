<?php

namespace Lle\DashboardBundle;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Lle\DashboardBundle\Entity\Widget;


/**
 * Description of WidgetProvider.
 */
class WidgetProvider
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage
     */
    protected $security;

    /**
     *
     */
    protected $widgetTypes;
    
    /**
     * 
     * @param \Doctrine\ORM\EntityManager $em
     * @param Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $security
     */
    public function __construct(EntityManagerInterface $em, TokenStorageInterface $security, iterable $widget_types)
    {
        $this->em = $em;
        $this->security = $security;
        
        foreach($widget_types[0] as $id => $w_service) {
            $this->widgetTypes[$w_service->getType()] = $w_service;
        }
    }



    /**
     * 
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getWidgetTypes()
    {
        return $this->widgetTypes;
    }

    /**
     * 
     * @param string $widget_type
     * @return Widget\WidgetTypeInterface
     */
    public function getWidgetType($widget_type)
    {
        if (array_key_exists($widget_type, $this->widgetTypes)) {
            return clone $this->widgetTypes[$widget_type];
        }
    }

    /**
     * Returns current user's widgets
     */
    public function getMyWidgets()
    {
        // Get user.
        $user = $this->security->getToken()->getUser();
        if (!is_object($user)) {
            return [];
        }

        // Get user's widgets.
        $myWidgets = $this->em->getRepository(Widget::class)
                ->getMyWidgets($user)
                ->getQuery()
                ->getResult();
        
        // Initialize actual widgets.
        return $this->initializeWidgets($myWidgets);
    }

    /**
     * Returns default widgets.
     */
    public function getDefaultWidgets()
    {
        $defaultWidgets = $this->em->getRepository(Widget::class)->getDefaultWidgets();

        return $this->initializeWidgets($defaultWidgets);
    }

    /**
     * Convert Widgets entites into actual Widgets (widget types)
     */
    private function initializeWidgets($widgets)
    {
        $return = [];
        foreach ($widgets as $widget) {

            $widgetType = $this->getWidgetType($widget->getType());
            if ($widgetType) {  // the widget could have been deleted
                $return[] = $widgetType->setParams($widget);
            }
            
        }
        return $return;
    }

    /**
     * Sert à copier les widgets par défaut vers les widgets de l'utilisateur.
     * Cela évite que l'utilisateur puisse modifier les widgets par défaut
     */
    public function setDefaultWidgetsForUser($user_id)
    {
        if ($user_id) {
            $sql = "
                INSERT INTO widgets
                SELECT
                    NULL AS id,
                    x,
                    y,
                    width,
                    height,
                    type,
                    " . $user_id . " AS user_id,
                    NULL AS config,
                    NULL AS title
                FROM widgets
                WHERE user_id IS NULL
            ";

            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->execute();
        }
    }
}

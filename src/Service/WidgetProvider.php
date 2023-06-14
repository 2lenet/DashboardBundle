<?php

namespace Lle\DashboardBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Entity\Widget;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WidgetProvider
{
    protected EntityManagerInterface $em;

    protected TokenStorageInterface $security;

    protected ?array $widgetTypes;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $security, iterable $widgetTypes)
    {
        $this->em = $em;
        $this->security = $security;

        /** @var WidgetTypeInterface $widgetType */
        foreach ($widgetTypes as $widgetType) {
            $this->widgetTypes[$widgetType->getType()] = $widgetType;
        }
    }

    public function getWidgetTypes(): ?array
    {
        return $this->widgetTypes;
    }

    public function getWidgetType($widgetType)
    {
        if (array_key_exists($widgetType, $this->widgetTypes)) {
            return clone $this->widgetTypes[$widgetType];
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
     * Initialize default widgets for an user, by copy
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

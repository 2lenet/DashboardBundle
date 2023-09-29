<?php

namespace Lle\DashboardBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Entity\Widget;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WidgetProvider
{
    protected ?array $widgetTypes;

    public function __construct(
        protected EntityManagerInterface $em,
        protected TokenStorageInterface $security,
        iterable $widgetTypes
    ) {
        /** @var WidgetTypeInterface $widgetType */
        foreach ($widgetTypes as $widgetType) {
            $this->widgetTypes[$widgetType->getType()] = $widgetType;
        }
    }

    public function getWidgetTypes(): ?array
    {
        return $this->widgetTypes;
    }

    public function getWidgetType(?string $widgetType): ?WidgetTypeInterface
    {
        if (array_key_exists($widgetType, $this->widgetTypes)) {
            return clone $this->widgetTypes[$widgetType];
        }

        return null;
    }

    /**
     * Returns current user's widgets
     */
    public function getMyWidgets(): array
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
     * Convert Widgets entites into actual Widgets (widget types)
     */
    private function initializeWidgets(array $widgets): array
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
    public function setDefaultWidgetsForUser(int $userId): void
    {
        if ($userId) {
            $sql = "
                INSERT INTO widgets
                SELECT
                    NULL AS id,
                    x,
                    y,
                    width,
                    height,
                    type,
                    " . $userId . " AS user_id,
                    NULL AS config,
                    NULL AS title
                FROM widgets
                WHERE user_id IS NULL
            ";

            $stmt = $this->em->getConnection()->prepare($sql);
            $stmt->executeQuery();
        }
    }
}

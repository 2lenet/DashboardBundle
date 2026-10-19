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
            if ($widgetType->getType()) {
                $this->widgetTypes[$widgetType->getType()] = $widgetType;
            }
        }
    }

    public function getWidgetTypes(): ?array
    {
        return $this->widgetTypes;
    }

    public function getWidgetType(string $widgetType): ?WidgetTypeInterface
    {
        /** @var array $widgetTypes */
        $widgetTypes = $this->widgetTypes;

        if (array_key_exists($widgetType, $widgetTypes)) {
            /** @var WidgetTypeInterface $widget */
            $widget = $widgetTypes[$widgetType];

            return clone $widget;
        }

        return null;
    }

    /**
     * Returns current user's widgets
     */
    public function getMyWidgets(): array
    {
        // Get user.
        $user = $this->security->getToken()?->getUser();
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
        if (!$userId) {
            return;
        }

        /** @var Widget[] $defaultWidgets */
        $defaultWidgets = $this->em->getRepository(Widget::class)->findBy(['userId' => null]);

        foreach ($defaultWidgets as $defaultWidget) {
            $this->em->persist(
                (new Widget())
                    ->setX($defaultWidget->getX())
                    ->setY($defaultWidget->getY())
                    ->setWidth($defaultWidget->getWidth())
                    ->setHeight($defaultWidget->getHeight())
                    ->setType($defaultWidget->getType())
                    ->setUserId($userId)
            );
        }

        $this->em->flush();
    }
}

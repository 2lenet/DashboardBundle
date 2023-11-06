<?php

namespace Lle\DashboardBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Entity\Widget;
use Lle\DashboardBundle\Service\WidgetCompacterService;
use Lle\DashboardBundle\Service\WidgetProvider;
use Lle\DashboardBundle\Widgets\AbstractWidget;
use Lle\DashboardBundle\Widgets\Stats;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TokenStorageInterface $tokenStorage,
        private WidgetCompacterService $widgetCompacter,
        protected CacheInterface $cache,
        protected KernelInterface $kernel
    ) {
    }

    #[Route('/dashboard/add_widget/{type}', name: 'add_widget', options: ['expose' => true])]
    public function addWidget(WidgetProvider $provider, mixed $type): Response
    {
        /** @var WidgetTypeInterface $widgetType */
        $widgetType = $provider->getWidgetType($type);

        /** @var object $user */
        $user = $this->getUser();
        $userId = method_exists($user, 'getId') ? $user->getId() : null;

        $widget = new Widget();
        $widget->importConfig($widgetType);
        $widget->setUserId($userId);

        // We just put the new widget under the existing ones
        $bottomWidget = $this->em->getRepository(Widget::class)->getBottomWidget($userId);
        if ($bottomWidget) {
            $widget->setY($bottomWidget->getY() + $bottomWidget->getHeight());
        }

        $this->em->persist($widget);
        $this->em->flush();

        return $this->forward(self::class . '::renderWidget', ['id' => $widget->getId()]);
    }

    #[Route('/dashboard/remove_widget/{id}', name: 'remove_widget', options: ['expose' => true])]
    public function removeWidget(int $id): JsonResponse
    {
        $widgetRepository = $this->em->getRepository(Widget::class);
        $widget = $widgetRepository->find($id);

        if ($widget) {
            $this->em->remove($widget);

            /** @var UserInterface $user */
            $user = $this->getUser();

            $widgets = $widgetRepository->getWidgetsOrderedByY($user);
            $this->widgetCompacter->compactY($widgets);
            $this->em->flush();

            $widgets = $widgetRepository->getWidgetsOrderedByY($user);
            $this->widgetCompacter->compactY($widgets);

            $this->em->flush();
        }

        return new JsonResponse(true);
    }

    #[Route('/dashboard/update_widget/{id}/{x}/{y}/{width}/{height}', name: 'update_widget', options: ['expose' => true])]
    public function updateWidget(int $id, ?int $x, ?int $y, ?int $width, ?int $height): JsonResponse
    {
        $widget = $this->em->getRepository(Widget::class)->find($id);

        if ($widget) {
            $widget
                ->setX($x)
                ->setY($y)
                ->setWidth($width)
                ->setHeight($height);

            $this->em->flush();
        }

        return new JsonResponse(true);
    }

    #[Route('/dashboard/update_title/{id}/{title}', name: 'update_title', options: ['expose' => true])]
    public function updateWidgetTitle(int $id, ?string $title): JsonResponse
    {
        $widget = $this->em->getRepository(Widget::class)->find($id);

        if ($widget) {
            $widget->setTitle($title);
            $this->em->flush();
        }

        return new JsonResponse(true);
    }

    #[Route('/dashboard/render_widget/{id}', name: 'render_widget', options: ['expose' => true])]
    public function renderWidget(WidgetProvider $provider, int $id): Response
    {
        $widget = $this->em->getRepository(Widget::class)->find($id);

        if ($widget) {
            /** @var WidgetTypeInterface $widgetType */
            $widgetType = $provider->getWidgetType((string)$widget->getType());
            $widgetType->setParams($widget);
            $content = $this->getWidgetContent($widgetType);

            return new Response($content);
        }

        throw $this->createNotFoundException();
    }

    #[Route('/dashboard/widget_save_config/{id}/{form}', name: 'widget_save_config')]
    public function saveConfig(Request $request, int $id, mixed $form): Response
    {
        $params = $request->request->all();
        /** @var array|null $config */
        $config = array_key_exists($form, $params) ? $params[$form] : null;

        $widget = $this->em->getRepository(Widget::class)->find($id);
        if ($widget) {
            $widget->setConfig($config);
            $this->em->flush();
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse("OK");
        }

        return $this->redirectToRoute("homepage");
    }

    /**
     * Reset config and title of widget.
     */
    #[Route('/dashboard/widget_reset_config/{id}', name: 'widget_reset_config')]
    public function resetConfig(int $id): Response
    {
        $widget = $this->em->getRepository(Widget::class)->find($id);

        if ($widget) {
            $widget->setTitle(null);
            $widget->setConfig(null);
            $this->em->flush();
        }

        return $this->redirectToRoute("homepage");
    }

    /**
     * Delete current user's widgets.
     */
    #[Route('/dashboard/delete_my_widgets', name: 'delete_my_widgets')]
    public function deleteMyWidgets(): Response
    {
        $user = $this->getUser();

        if ($user && method_exists($user, 'getId')) {
            $this->em->getRepository(Widget::class)->deleteMyWidgets($user->getId());
        }

        return $this->redirectToRoute("homepage");
    }

    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function dashboard(WidgetProvider $provider): Response
    {
        $widgets = [];
        $widgetTypes = $provider->getWidgetTypes();

        $user = $this->getUser();
        if ($user) {
            // l'utilisateur n'a pas de widgets, on met ceux par défaut.
            $widgets = $provider->getMyWidgets();
            if (!$widgets && method_exists($user, 'getId')) {
                $provider->setDefaultWidgetsForUser($user->getId());
                $widgets = $provider->getMyWidgets();
            }
        }

        $widgetsView = [];
        /** @var AbstractWidget $widget */
        foreach ($widgets as $widget) {
            if ($widget->supports()) {
                if ($widget->supportsAjax()) {
                    $widgetsView[] = $this->renderView("@LleDashboard/widget/empty_widget.html.twig", [
                        "widget" => $widget,
                    ]);
                } else {
                    $widgetsView[] = $this->getWidgetContent($widget);
                }
            }
        }

        return $this->render("@LleDashboard/dashboard/dashboard.html.twig", array(
            "widgets_data" => $widgetsView,
            "widgets" => $widgets,
            "widget_types" => $widgetTypes,
        ));
    }

    /**
     * Sets the current user's dashboard as default dashboard
     */
    #[Route('/dashboard/admin/default')]
    public function setMyDashboardAsDefault(): Response
    {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");

        $user = $this->getUser();

        if ($user && method_exists($user, 'getId')) {
            $repo = $this->em->getRepository(Widget::class);

            $repo->deleteDefaultDashboard()->getQuery()->execute();
            $repo->setDashboardAsDefault($user->getId())->getQuery()->execute();
        }

        return $this->redirectToRoute("homepage");
    }

    /**
     * Delete all dashboards (not the default one)
     */
    #[Route('/dashboard/admin/reset-all')]
    public function deleteAllUserDashboards(): Response
    {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");

        $repo = $this->em->getRepository(Widget::class);

        $repo->deleteAllUserDashboards()->getQuery()->execute();

        return $this->redirectToRoute("homepage");
    }

    protected function getUser(): ?UserInterface
    {
        if ($this->tokenStorage->getToken() && is_object($this->tokenStorage->getToken()->getUser())) {
            return $this->tokenStorage->getToken()->getUser();
        }

        return null;
    }

    protected function serializeWidget(WidgetTypeInterface $widget): array
    {
        return [
            "id" => $widget->getId(),
            "x" => $widget->getX(),
            "y" => $widget->getY(),
            "w" => $widget->getWidth(),
            "h" => $widget->getHeight(),
            "content" => $widget->render(),
        ];
    }

    protected function getWidgetContent(?WidgetTypeInterface $widgetType): string
    {
        $content = "";
        if ($widgetType) {
            if ($widgetType->getCacheTimeout() && !$this->kernel->isDebug()) {
                $uniqueKey = "widget_cache_" . $widgetType->getCacheKey();
                $content = $this->cache->get($uniqueKey, function (ItemInterface $item) use ($widgetType) {
                    $item->expiresAfter($widgetType->getCacheTimeout());

                    return (string)$widgetType->render();
                });
            } else {
                $content = $widgetType->render();
            }
        }

        return (string)$content;
    }
}

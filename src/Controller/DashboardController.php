<?php

namespace Lle\DashboardBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Entity\Widget;
use Lle\DashboardBundle\Service\WidgetCompacterService;
use Lle\DashboardBundle\Service\WidgetProvider;
use Lle\DashboardBundle\Widgets\AbstractWidget;
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
    private EntityManagerInterface $em;

    private TokenStorageInterface $tokenStorage;

    private WidgetCompacterService $widgetCompacter;

    protected CacheInterface $cache;

    protected KernelInterface $kernel;

    public function __construct(
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage,
        WidgetCompacterService $widgetCompacter,
        CacheInterface $cache,
        KernelInterface $kernel
    )
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
        $this->widgetCompacter = $widgetCompacter;
        $this->cache = $cache;
        $this->kernel = $kernel;
    }

    /**
     * @Route("/dashboard/add_widget/{type}", options={"expose"=true}, name="add_widget")
     */
    public function addWidgetAction(WidgetProvider $provider, $type)
    {
        $widgetType = $provider->getWidgetType($type);

        $user_id = method_exists($this->getUser(), 'getId') ? $this->getUser()->getId() : null;

        $widget = new Widget();
        $widget->importConfig($widgetType);
        $widget->setUserId($user_id);

        // We just put the new widget under the existing ones
        $bottomWidget = $this->em->getRepository(Widget::class)->getBottomWidget($user_id);
        if ($bottomWidget) {
            $widget->setY($bottomWidget->getY() + $bottomWidget->getHeight());
        }

        $this->em->persist($widget);
        $this->em->flush();

        return $this->forward(self::class . '::renderWidget', ['id' => $widget->getId()]);
    }

    /**
     * @Route("/dashboard/remove_widget/{id}", options={"expose"=true}, name="remove_widget")
     */
    public function removeWidgetAction($id): JsonResponse
    {
        $widgetRepository = $this->em->getRepository(Widget::class);
        $widget = $widgetRepository->find($id);

        if ($widget) {
            $this->em->remove($widget);

            $widgets = $widgetRepository->getWidgetsOrderedByY($this->getUser());
            $this->widgetCompacter->compactY($widgets);
            $this->em->flush();

            $widgets = $widgetRepository->getWidgetsOrderedByY($this->getUser());
            $this->widgetCompacter->compactY($widgets);

            $this->em->flush();
        }

        return new JsonResponse(true);
    }

    /**
     * @Route("/dashboard/update_widget/{id}/{x}/{y}/{width}/{height}", options={"expose"=true}, name="update_widget")
     */
    public function updateWidgetAction($id, $x, $y, $width, $height)
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

    /**
     * @Route("/dashboard/update_title/{id}/{title}", options={"expose"=true}, name="update_title")
     */
    public function updateWidgetTitleAction($id, $title)
    {
        $widget = $this->em->getRepository(Widget::class)->find($id);

        if ($widget) {
            $widget->setTitle($title);
            $this->em->flush();
        }

        return new JsonResponse(true);
    }

    /**
     * @Route("/dashboard/render_widget/{id}", options={"expose"=true}, name="render_widget")
     */
    public function renderWidgetAction(WidgetProvider $provider, $id)
    {
        $widget = $this->em->getRepository(Widget::class)->find($id);

        if ($widget) {
            $widgetType = $provider->getWidgetType($widget->getType());
            $widgetType->setParams($widget);
            $content = $this->getWidgetContent($widgetType);

            return new Response($content);
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/dashboard/widget_save_config/{id}/{form}", name="widget_save_config")
     */
    public function saveConfigAction(Request $request, WidgetProvider $provider, $id, $form)
    {
        $params = $request->request->all();
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
     * @Route("/dashboard/widget_reset_config/{id}", name="widget_reset_config")
     */
    public function resetConfigAction($id)
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
     * @Route("/dashboard/delete_my_widgets", name="delete_my_widgets")
     */
    public function deleteMyWidgetsAction()
    {
        $user = $this->getUser();
        if ($user) {
            $this->em->getRepository(Widget::class)->deleteMyWidgets($user->getId());
        }

        return $this->redirectToRoute("homepage");
    }

    /**
     * @Route("/", name="homepage", methods="GET")
     */
    public function dashboardAction(WidgetProvider $provider)
    {
        $user = $this->getUser();
        $widgetTypes = $provider->getWidgetTypes();
        if ($user) {
            $widgets = $provider->getMyWidgets();

            // l'utilisateur n'a pas de widgets, on met ceux par défaut.
            if (!$widgets) {
                $provider->setDefaultWidgetsForUser($user->getId());
                $widgets = $provider->getMyWidgets();
            }
        } else {
            $widgets = [];
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
     * @Route("/dashboard/admin/default")
     *
     * Sets the current user's dashboard as default dashboard
     */
    public function setMyDashboardAsDefault()
    {
        $this->denyAccessUnlessGranted("ROLE_SUPER_ADMIN");

        $user = $this->getUser();

        if ($user) {
            $repo = $this->em->getRepository(Widget::class);

            $repo->deleteDefaultDashboard()->getQuery()->execute();
            $repo->setDashboardAsDefault($user->getId())->getQuery()->execute();
        }

        return $this->redirectToRoute("homepage");
    }

    /**
     * @Route("/dashboard/admin/reset-all")
     *
     * Delete all dashboards (not the default one)
     */
    public function deleteAllUserDashboardsAction()
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

    protected function getWidgetContent(?AbstractWidget $widgetType): string
    {
        $content = "";
        if ($widgetType) {
            if ($widgetType->getCacheTimeout() && !$this->kernel->isDebug()) {
                $uniqueKey = "widget_cache_" . $widgetType->getCacheKey();
                $content = $this->cache->get($uniqueKey, function (ItemInterface $item) use ($widgetType) {
                    $item->expiresAfter($widgetType->getCacheTimeout());

                    return $widgetType->render();
                });
            } else {
                $content = $widgetType->render();
            }
        }

        return $content;
    }
}

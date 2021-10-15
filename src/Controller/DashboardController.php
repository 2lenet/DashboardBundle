<?php

namespace Lle\DashboardBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Entity\Widget;
use Lle\DashboardBundle\Service\WidgetProvider;
use Lle\DashboardBundle\Widgets\AbstractWidget;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Akcja controller.
 */
class DashboardController extends AbstractController
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(EntityManagerInterface $em, TokenStorageInterface $tokenStorage)
    {
        $this->em = $em;
        $this->tokenStorage = $tokenStorage;
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

        return $this->forward(self::class .'::renderWidget', ['id'=>$widget->getId()]);
    }

    /**
     * @Route("/dashboard/remove_widget/{id}", options={"expose"=true}, name="remove_widget")
     */
    public function removeWidgetAction($id)
    {
        $widget = $this->em->getRepository(Widget::class)->find($id);

        if ($widget) {
            $this->em->remove($widget);
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
                ->setHeight($height)
            ;

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
    public function renderWidget(CacheInterface $cache ,WidgetProvider $provider, $id)
    {
        $widget = $this->em->getRepository(Widget::class)->find($id);

        if ($widget) {
            $widgetType = $provider->getWidgetType($widget->getType());
            $widgetType->setParams($widget);
            if($widgetType->getCacheTimeout()) {
                $uniqueKey = "widget_cache_" . $widgetType->getCacheKey();
                $content = $cache->get($uniqueKey, function (ItemInterface $item) use ($widgetType) {
                    $item->expiresAfter($widgetType->getCacheTimeout());
                    return $widgetType->render();
                });
            }else{
                $content = $widgetType->render();
            }

            return new JsonResponse($this->serializeWidget($widgetType));
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/dashboard/widget_save_config/{id}", name="widget_save_config")
     */
    public function saveConfig(Request $request, WidgetProvider $provider, $id)
    {
        $config = $request->request->get("form")["json_form_".$id];
        $widget = $this->em->getRepository(Widget::class)->find($id);
        
        if ($widget) {
            $widget->setConfig($config);
            $this->em->flush();
        }

        return $this->redirectToRoute("homepage");
    }

    /**
     * Reset config and title of widget.
     * @Route("/dashboard/widget_reset_config/{id}", name="widget_reset_config")
     */
    public function resetConfig($id)
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
    public function deleteMyWidgets()
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

        $widgets_view = [];
        foreach ($widgets as $w) {
            $widgets_view[] = $this->serializeWidget($w);
        }
        return $this->render("@LleDashboard/dashboard/dashboard.html.twig", array(
            "widgets_data" => $widgets_view,
            "widgets" =>$widgets,
            "widget_types" => $widgetTypes,
        ));
    }

    /**
     * @Route("/dashboard/admin/default")
     * @IsGranted("ROLE_SUPER_ADMIN")
     *
     * Sets the current user's dashboard as default dashboard
     */
    public function setMyDashboardAsDefault()
    {
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
     * @IsGranted("ROLE_SUPER_ADMIN")
     *
     * Delete all dashboards (not the default one)
     */
    public function deleteAllUserDashboards()
    {
        $repo = $this->em->getRepository(Widget::class);

        $repo->deleteAllUserDashboards()->getQuery()->execute();

        return $this->redirectToRoute("homepage");
    }

    protected function getUser()
    {
        if ($this->tokenStorage->getToken() && is_object($this->tokenStorage->getToken()->getUser())) {
            return $this->tokenStorage->getToken()->getUser();
        }

        return null;
    }

    protected function serializeWidget(AbstractWidget $widget): array
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
}

<?php

namespace Lle\DashboardBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Entity\Widget;
use Lle\DashboardBundle\Service\WidgetCompacterService;
use Lle\DashboardBundle\Contracts\StaticWidgetProviderInterface;
use Lle\DashboardBundle\Service\WidgetProvider;
use Lle\DashboardBundle\Widgets\AbstractWidget;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class StaticDashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TokenStorageInterface $tokenStorage,
        protected CacheInterface $cache,
        protected KernelInterface $kernel,
    ) {
    }

    #[Route('/dashboard/static', name: 'static_dashboard')]
    public function staticDashboard( StaticWidgetProviderInterface $provider): Response
    {
        $widgets = $provider->getMyWidgets();
        return $this->render("@LleDashboard/dashboard/static_dashboard.html.twig", [
            "widgets" => $widgets,
        ]);
    }

    #[Route('/dashboard/render_static_widget/{static_index}', name: 'render_static_widget', options: ['expose' => true])]
    public function renderStaticWidget(StaticWidgetProviderInterface $provider, string $static_index): Response
    {
        $widget = $provider->getWidget($static_index);
        if ($widget) {
            return new Response($widget->renderStatic());
        }

        throw $this->createNotFoundException();
    }
}

<?php

namespace Lle\DashboardBundle\Controller;

use Lle\DashboardBundle\Service\StaticDashboardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StaticDashboardController extends AbstractController
{
    #[Route('/dashboard/static', name: 'static_dashboard')]
    public function staticDashboard(StaticDashboardService $staticDashboard): Response
    {
        $isTabbed = $staticDashboard->isTabbed();

        return $this->render("@LleDashboard/dashboard/static_dashboard.html.twig", [
            "tabs" => $isTabbed ? $staticDashboard->getVisibleTabs() : [],
            "widgets" => $isTabbed ? [] : $staticDashboard->getVisibleWidgets(),
        ]);
    }

    #[Route('/dashboard/render_static_widget/{staticIndex}', name: 'render_static_widget', options: ['expose' => true])]
    public function renderStaticWidget(StaticDashboardService $staticDashboard, string $staticIndex): Response
    {
        $widget = $staticDashboard->getWidget($staticIndex);
        if (!$widget) {
            throw $this->createNotFoundException(sprintf('There is no static widget "%s".', $staticIndex));
        }

        if (!$staticDashboard->isGranted($widget)) {
            throw $this->createAccessDeniedException(sprintf(
                'The static widget "%s" requires the role "%s".',
                $staticIndex,
                $widget->getRole(),
            ));
        }

        return new Response($staticDashboard->renderWidget($widget));
    }
}

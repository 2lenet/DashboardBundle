<?php

namespace Lle\DashboardBundle\Controller;

use Lle\DashboardBundle\Contracts\StaticWidgetProviderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StaticDashboardController extends AbstractController
{
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
            return new Response($widget->render());
        }

        throw $this->createNotFoundException();
    }
}

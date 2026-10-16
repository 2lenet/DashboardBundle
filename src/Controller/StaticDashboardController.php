<?php

namespace Lle\DashboardBundle\Controller;

use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Dto\ConfiguredStaticWidget;
use Lle\DashboardBundle\Service\StaticDashboardService;
use Lle\DashboardBundle\Service\StaticWidgetConfigService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StaticDashboardController extends AbstractController
{
    public const string RESET_CSRF_TOKEN_PREFIX = 'static_widget_reset_';

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
    public function renderStaticWidget(
        StaticDashboardService $staticDashboard,
        StaticWidgetConfigService $widgetConfig,
        string $staticIndex,
    ): Response {
        $widget = $this->getGrantedWidget($staticDashboard, $staticIndex);

        return $this->renderConfiguredWidget(
            $staticDashboard,
            $staticIndex,
            $widgetConfig->configure($staticIndex, $widget),
        );
    }

    #[Route('/dashboard/print_static_widget/{staticIndex}', name: 'print_static_widget', options: ['expose' => true])]
    public function printStaticWidget(
        StaticDashboardService $staticDashboard,
        StaticWidgetConfigService $widgetConfig,
        string $staticIndex,
    ): Response {
        $widget = $this->getGrantedWidget($staticDashboard, $staticIndex);
        if (!$staticDashboard->isPrintable($widget)) {
            throw $this->createNotFoundException(sprintf('The static widget "%s" is not printable.', $staticIndex));
        }

        $configuredWidget = $widgetConfig->configure($staticIndex, $widget)->getWidget();

        return $this->render('@LleDashboard/dashboard/print_static_widget.html.twig', [
            'widget' => $configuredWidget,
            'content' => $staticDashboard->renderWidget($configuredWidget),
            'cssTags' => $configuredWidget->getCssTagsForPrint(),
            'jsTags' => $configuredWidget->getJsTagsForPrint(),
        ]);
    }

    #[Route('/dashboard/static_widget_config/{staticIndex}', name: 'static_widget_save_config', methods: ['POST'])]
    public function saveStaticWidgetConfig(
        StaticDashboardService $staticDashboard,
        StaticWidgetConfigService $widgetConfig,
        Request $request,
        string $staticIndex,
    ): Response {
        $widget = $this->getConfigurableWidget($staticDashboard, $widgetConfig, $staticIndex);
        $configured = $widgetConfig->save($staticIndex, $widget, $request);
        $isValid = $configured->getForm()?->vars['valid'] ?? false;

        return $this->renderConfiguredWidget(
            $staticDashboard,
            $staticIndex,
            $configured,
            $isValid ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY,
        );
    }

    #[Route('/dashboard/static_widget_config/{staticIndex}/reset', name: 'static_widget_reset_config', methods: ['POST'])]
    public function resetStaticWidgetConfig(
        StaticDashboardService $staticDashboard,
        StaticWidgetConfigService $widgetConfig,
        Request $request,
        string $staticIndex,
    ): Response {
        $widget = $this->getConfigurableWidget($staticDashboard, $widgetConfig, $staticIndex);

        if (!$this->isCsrfTokenValid(self::RESET_CSRF_TOKEN_PREFIX . $staticIndex, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        return $this->renderConfiguredWidget(
            $staticDashboard,
            $staticIndex,
            $widgetConfig->reset($staticIndex, $widget),
        );
    }

    protected function getGrantedWidget(StaticDashboardService $staticDashboard, string $staticIndex): WidgetTypeInterface
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

        return $widget;
    }

    protected function getConfigurableWidget(
        StaticDashboardService $staticDashboard,
        StaticWidgetConfigService $widgetConfig,
        string $staticIndex,
    ): WidgetTypeInterface {
        $widget = $this->getGrantedWidget($staticDashboard, $staticIndex);
        if (!$widgetConfig->isConfigurable($widget)) {
            throw $this->createNotFoundException(sprintf('The static widget "%s" is not configurable.', $staticIndex));
        }

        return $widget;
    }

    protected function renderConfiguredWidget(
        StaticDashboardService $staticDashboard,
        string $staticIndex,
        ConfiguredStaticWidget $configured,
        int $status = Response::HTTP_OK,
    ): Response {
        $widget = $configured->getWidget();

        return $this->render('@LleDashboard/dashboard/_static_widget.html.twig', [
            'staticIndex' => $staticIndex,
            'widget' => $widget,
            'printable' => $staticDashboard->isPrintable($widget),
            'form' => $configured->getForm(),
            'customized' => $configured->isCustomized(),
            'content' => $staticDashboard->renderWidget($widget),
        ], new Response(null, $status));
    }
}

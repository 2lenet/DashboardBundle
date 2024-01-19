<?php

namespace Lle\DashboardBundle\Widgets;

use Lle\DashboardBundle\Form\ChartWidgetType;

class ChartWidget extends AbstractWidget
{
    public function __construct(
        private iterable $chartProvider,
    ) {
    }

    public function render(): string
    {
        $chartProvider = $this->chartProvider instanceof \Traversable ? iterator_to_array(
            $this->chartProvider
        ) : $this->chartProvider;

        $chart = null;
        $chartList = [];
        // Add this for a blank choice
        $chartList[''] = '';

        $config = $this->getConfig('chart', '');
        if ($config) {
            [$serviceId, $chartKey] = explode('-', $config, 2);
            $provider = $chartProvider[$serviceId];
            $chart = $provider->getChart($chartKey);
        }

        foreach ($chartProvider as $key => $provider) {
            foreach ($provider->getChartList() as $dataConf) {
                $chartList[$dataConf] = $key . '-' . $dataConf;
            }
        }

        $form = $this->createForm(ChartWidgetType::class, null, ['config' => $config, 'chartList' => $chartList]);

        return $this->twig('@LleDashboard/widget/chart_widget.html.twig', [
            'widget' => $this,
            'config_form' => $form->createView(),
            'chart' => $chart,
        ]);
    }

    public function getName(): string
    {
        return 'widget.charts.title';
    }
}

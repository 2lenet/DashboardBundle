<?php

namespace Lle\DashboardBundle\Widgets;

use Lle\DashboardBundle\Form\StatsType;
use Symfony\Component\DependencyInjection\Container;

class Stats extends AbstractWidget
{
    public function __construct(
        private iterable $dataProvider,
        private Container $container,
    ) {
    }

    public function render(): string
    {
        $data = [];
        $dataSources = [];
        // Add this for a blank choice
        $dataSources[''] = '';

        $config = $this->getConfig('dataSource', '');
        if ($config) {
            if (substr_count($config, '-') === 3) {
                $params = explode('-', $config);

                foreach ($this->dataProvider as $provider) {
                    if (get_class($provider) === $params[0]) {
                        $data = $provider->getData($params[1], $params[2], $params[3]);
                    }
                }
            }
        }

        foreach ($this->dataProvider as $provider) {
            foreach ($provider->getDataConf() as $key => $dataConf) {
                $dataSources[$dataConf] = get_class($provider) . '-' . $dataConf;
            }
        }

        $form = $this->createForm(StatsType::class, null, ['config' => $config, 'dataSources' => $dataSources]);

        return $this->twig('@LleDashboard/widget/stats_widget.html.twig', [
            'widget' => $this,
            'config_form' => $form->createView(),
            'data' => $data,
        ]);
    }

    public function getName(): string
    {
        return 'widget.stats.title';
    }
}

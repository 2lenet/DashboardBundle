<?php

namespace Lle\DashboardBundle\Widgets;

use Lle\DashboardBundle\Form\StatsType;

class Stats extends AbstractWidget
{
    public function __construct(
        private iterable $dataProvider,
    ) {
    }

    public function render(): string
    {
        $dataProvider = $this->dataProvider instanceof \Traversable ? iterator_to_array(
            $this->dataProvider
        ) : $this->dataProvider;

        $data = [];
        $dataSources = [];
        // Add this for a blank choice
        $dataSources[''] = '';

        $config = $this->getConfig('dataSource', '');
        if ($config) {
            if (substr_count($config, '-') === 3) {
                list($serviceId, $valueSpec, $groupSpec, $number) = explode('-', $config);

                $provider = $dataProvider[$serviceId];
                $data = $provider->getData($valueSpec, $groupSpec, $number);
            }
        }

        foreach ($dataProvider as $key => $provider) {
            foreach ($provider->getDataConf() as $dataConf) {
                $dataSources[$dataConf] = $key . '-' . $dataConf;
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

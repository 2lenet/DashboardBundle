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
        $datasources = [];
        $datasources[''] = '';

        $data = [];
        $conf = $this->getConfig('config', '');

        foreach ($this->dataProvider as $provider) {
            foreach ($provider->getDataConf() as $key => $conf) {
                $datasources[$conf] = get_class($provider) . '-' . $conf;
            }
        }

        $form = $this->createForm(StatsType::class, null, ['configs' => $datasources, 'config' => $conf]);

        if ($conf) {
            if (substr_count($conf, '-') === 3) {
                $params = explode('-', $conf);
                $repository = $this->container->get($params[0]);

                if ($repository && method_exists($repository, 'getData')) {
                    $data = $repository->getData($params[1], $params[2], $params[3]);
                }
            }
        }

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

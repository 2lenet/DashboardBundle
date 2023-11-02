<?php

namespace Lle\DashboardBundle\Widgets;

use Lle\DashboardBundle\Form\StatsType;
use Symfony\Component\Form\FormInterface;

class Stats extends AbstractWidget
{
    public function __construct(private iterable $dataProvider)
    {
    }

    public function render(): string
    {
        $providers = [];
        $providers[] = '';
        $providerConfs = [];
        foreach ($this->dataProvider as $provider) {
            $providers[] = get_class($provider);
            $providerConfs[get_class($provider)] = $provider->getDataConf();
        }

        $form = $this->createForm(StatsType::class, null, ['providers' => $providers, 'confs' => $providerConfs]);

        return $this->twig('@LleDashboard/widget/stats_widget.html.twig', [
            'widget' => $this,
            'config_form' => $form->createView(),
        ]);
    }

    public function getName(): string
    {
        return 'widget.stats.title';
    }
}

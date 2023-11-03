<?php

namespace Lle\DashboardBundle\Widgets;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Form\StatsType;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormInterface;

class Stats extends AbstractWidget
{
    public function __construct(
        private iterable $dataProvider,
        private EntityManagerInterface $em,
        private Container $container,
    ) {
    }

    public function render(): string
    {
        $datasources = [];
        $datasources[''] = '';
        foreach ($this->dataProvider as $provider) {
            $repositoryParts = explode('\\', get_class($provider));
            $repositoryClass = end($repositoryParts);

            foreach ($provider->getDataConf() as $key => $conf) {
                $datasources[$conf] = $repositoryClass . '_' . $conf;
            }
        }

        $data = [];
        $conf = $this->getConfig('conf', '');
        if ($conf) {
            $confParts = explode('_', $conf);
            $repositoryClass = 'App\\Repository\\' . $confParts[0];
            $repository = $this->container->get($repositoryClass);

            $params = explode('-', $confParts[1]);

            $data = $repository->getData($params[0], $params[1], $params[2]);
        }

        $form = $this->createForm(StatsType::class, null, ['confs' => $datasources, 'conf' => $conf]);

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

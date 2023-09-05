<?php

namespace Lle\DashboardBundle\Maker;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Component\Console\Input\InputInterface;

trait MakerTrait
{
    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $io->text('Create the widget');
        try {
            $this->createWidget($input, $io, $generator);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }

        try {
            $this->createTemplate($input, $io, $generator);
        } catch (\Exception $e) {
            $io->error($e->getMessage());
        }
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Annotation::class,
            'annotations'
        );
    }
}

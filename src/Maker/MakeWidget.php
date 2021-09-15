<?php

declare(strict_types=1);

namespace Lle\DashboardBundle\Maker;

use Doctrine\Common\Annotations\Annotation;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

final class MakeWidget extends AbstractMaker
{

    /** @var FileManager */
    private $fileManager;

    /** @var DoctrineHelper */
    private $entityHelper;

    /** @var bool */
    private $withController;

    public function __construct(
    )
    {
    }

    public static function getCommandName(): string
    {
        return 'make:widget';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates a new widget class')
            ->addArgument(
                'namespace-widget',
                InputArgument::OPTIONAL,
                sprintf('Namespace for widget App/Widget/[...]/MyWidget.php ?')
            )
            ->addArgument(
                'widgetname',
                InputArgument::OPTIONAL,
                'The name of the widget'
            )
            ->setHelp((string)file_get_contents(__DIR__ . '/../Resources/help/make_widget.txt'));

        $inputConfig->setArgumentAsNonInteractive('namespace-widget');
        $inputConfig->setArgumentAsNonInteractive('widgetname');

    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('namespace-widget')) {
            $argument = $command->getDefinition()->getArgument('namespace-widget');
            $question = new Question($argument->getDescription(), 'Widget');
            $value = $io->askQuestion($question);
            $input->setArgument('namespace-widget', $value);
        }

        if (null === $input->getArgument('widgetname')) {
            $argument = $command->getDefinition()->getArgument('widgetname');
            $question = new Question($argument->getDescription(), 'mywidget');
            $value = $io->askQuestion($question);
            $input->setArgument('widgetname', $value);
        }
    }

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

    public
    function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Annotation::class,
            'annotations'
        );
    }

    private function createWidget(
        InputInterface $input,
        ConsoleStyle   $io,
        Generator      $generator
    ): string
    {
        $widgetname = $this->getStringArgument('widgetname', $input);
        $namespace = $this->getStringArgument('namespace-widget', $input);

        $template = 'EmptyWidget';
        $widgetClassNameDetails = $generator->createClassNameDetails(
            $widgetname,
            $namespace,
            'Widget'
        );
        $generator->generateClass(
            $widgetClassNameDetails->getFullName(),
            $this->getSkeletonTemplate( "widget/$template.php"),
            [
                'namespace' => 'App',
                'widgetname' => $widgetname,
                'classname' => ucfirst($widgetname).'Widget'
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
        return $widgetClassNameDetails->getFullName();
    }


    private function getSkeletonTemplate(string $templateName): string
    {
        return __DIR__ . '/../Resources/skeleton/' . $templateName;
    }

    private function createTemplate(
        InputInterface $input,
        ConsoleStyle   $io,
        Generator      $generator
    ): void
    {
        $widgetname = $this->getStringArgument('widgetname', $input);

        $generator->generateTemplate('widget/'.$widgetname.'.html.twig',
            $this->getSkeletonTemplate('widget/twig_emptywidget.tpl.php'),
            [
                'widgetname' => $widgetname,
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    private
    function getStringArgument(string $name, InputInterface $input): string
    {
        if (is_string($input->getArgument($name)) || is_null($input->getArgument($name))) {
            return (string)$input->getArgument($name);
        }
        throw new InvalidArgumentException($name . ' must be string type');
    }

    private
    function getBoolArgument(string $name, InputInterface $input): bool
    {
        if (is_string($input->getArgument($name)) || is_bool($input->getArgument($name))) {
            return (bool)$input->getArgument($name);
        }
        throw new InvalidArgumentException($name . ' must be bool type');
    }


}

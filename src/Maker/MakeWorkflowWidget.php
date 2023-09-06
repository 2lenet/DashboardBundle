<?php

declare(strict_types=1);

namespace Lle\DashboardBundle\Maker;

use Lle\CruditBundle\LleCruditBundle;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\KernelInterface;

class MakeWorkflowWidget extends AbstractMaker
{
    use MakerTrait;

    private KernelInterface $kernel;

    private DoctrineHelper $doctrineHelper;

    public function __construct(
        KernelInterface $kernel,
        DoctrineHelper $doctrineHelper
    ) {
        $this->kernel = $kernel;
        $this->doctrineHelper = $doctrineHelper;
    }

    public static function getCommandName(): string
    {
        return 'make:workflow-widget';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $bundleDir = $this->kernel->getBundle('LleDashboardBundle')->getPath();

        $command
            ->setDescription('Creates a new workflow widget class')
            ->addArgument(
                'widgetname',
                InputArgument::OPTIONAL,
                'Name of the widget ?'
            )
            ->addArgument(
                'namespace-widget',
                InputArgument::OPTIONAL,
                sprintf('Directory for widgets (src/[...]/MyWidget.php) ?')
            )
            ->addArgument(
                'entity',
                InputArgument::OPTIONAL,
                'Name of the entity containing the workflow ?'
            )
            ->addArgument(
                'workflow',
                InputArgument::OPTIONAL,
                'Name of the workflow ?'
            )
            ->setHelp((string)file_get_contents($bundleDir . '/Resources/help/make_widget.txt'));

        $inputConfig->setArgumentAsNonInteractive('widgetname');
        $inputConfig->setArgumentAsNonInteractive('namespace-widget');
        $inputConfig->setArgumentAsNonInteractive('workflow');
        $inputConfig->setArgumentAsNonInteractive('entity');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('namespace-widget') === null) {
            $argument = $command->getDefinition()->getArgument('namespace-widget');
            $question = new Question($argument->getDescription(), 'Widget');
            $value = $io->askQuestion($question);
            $input->setArgument('namespace-widget', $value);
        }

        if ($input->getArgument('widgetname') === null) {
            $argument = $command->getDefinition()->getArgument('widgetname');
            $question = new Question($argument->getDescription(), 'mywidget');
            $value = $io->askQuestion($question);
            $input->setArgument('widgetname', $value);
        }

        if ($input->getArgument('entity') === null) {
            $argument = $command->getDefinition()->getArgument('entity');
            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($this->doctrineHelper->getEntitiesForAutocomplete());
            $value = $io->askQuestion($question);
            $input->setArgument('entity', $value);
        }

        if ($input->getArgument('workflow') === null) {
            $argument = $command->getDefinition()->getArgument('workflow');
            $question = new Question($argument->getDescription());
            $value = $io->askQuestion($question);
            $input->setArgument('workflow', $value);
        }
    }

    private function createWidget(InputInterface $input, ConsoleStyle $io, Generator $generator): string
    {
        $widgetName = $this->getStringArgument('widgetname', $input);
        $namespace = $this->getStringArgument('namespace-widget', $input);
        $entity = $this->getStringArgument('entity', $input);
        $workflow = $this->getStringArgument('workflow', $input);

        $widgetClassNameDetails = $generator->createClassNameDetails($widgetName, $namespace);
        $generator->generateClass(
            $widgetClassNameDetails->getFullName(),
            $this->getSkeletonTemplate('widget/EmptyWorkflowWidget.php'),
            [
                'namespace' => 'App',
                'widgetname' => $widgetName,
                'classname' => ucfirst($widgetName),
                'entity' => $entity,
                'workflow' => $workflow
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);

        return $widgetClassNameDetails->getFullName();
    }

    private function getSkeletonTemplate(string $templateName): string
    {
        $bundleDir = $this->kernel->getBundle('LleDashboardBundle')->getPath();

        return $bundleDir . '/Resources/skeleton/' . $templateName;
    }

    private function createTemplate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $widgetname = $this->getStringArgument('widgetname', $input);
        $workflow = $this->getStringArgument('workflow', $input);
        $entity = $this->getStringArgument('entity', $input);

        $generator->generateTemplate(
            'widget/' . strtolower($widgetname) . '.html.twig',
            $this->getSkeletonTemplate('widget/twig_emptyworkflowwidget.tpl.php'),
            [
                'widgetname' => $widgetname,
                'entity' => $entity,
                'withLink' => class_exists(LleCruditBundle::class),
                'workflow' => $workflow
            ]
        );
        $generator->writeChanges();
        $this->writeSuccessMessage($io);
    }

    private function getStringArgument(string $name, InputInterface $input): string
    {
        if (is_string($input->getArgument($name)) || is_null($input->getArgument($name))) {
            return (string)$input->getArgument($name);
        }
        throw new InvalidArgumentException($name . ' must be string type');
    }
}

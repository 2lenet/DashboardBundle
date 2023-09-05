<?php

declare(strict_types=1);

namespace Lle\DashboardBundle\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

class MakeWidget extends AbstractMaker
{
    use MakerTrait;

    public static function getCommandName(): string
    {
        return 'make:widget';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->setDescription('Creates a new widget class')
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

    private function createWidget(InputInterface $input, ConsoleStyle $io, Generator $generator): string
    {
        $widgetname = $this->getStringArgument('widgetname', $input);
        $namespace = $this->getStringArgument('namespace-widget', $input);

        $template = 'EmptyWidget';
        $widgetClassNameDetails = $generator->createClassNameDetails(
            $widgetname,
            $namespace
        );
        $generator->generateClass(
            $widgetClassNameDetails->getFullName(),
            $this->getSkeletonTemplate("widget/$template.php"),
            [
                'namespace' => 'App',
                'widgetname' => $widgetname,
                'classname' => ucfirst($widgetname),
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

    private function createTemplate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $widgetname = $this->getStringArgument('widgetname', $input);

        $generator->generateTemplate('widget/' . strtolower($widgetname) . '.html.twig',
            $this->getSkeletonTemplate('widget/twig_emptywidget.tpl.php'),
            [
                'widgetname' => $widgetname,
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

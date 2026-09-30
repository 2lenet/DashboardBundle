<?php

namespace Lle\DashboardBundle\Service;

use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Dto\ConfiguredStaticWidget;
use Lle\DashboardBundle\Entity\StaticWidgetConfig;
use Lle\DashboardBundle\Repository\StaticWidgetConfigRepository;
use Lle\DashboardBundle\Widgets\AbstractWidget;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Widgets are services shared across users and, under a persistent worker, across requests:
 * the user's config is always applied to a clone, never to the service itself.
 */
class StaticWidgetConfigService
{
    public const string FORM_NAME_PREFIX = 'static_widget_config_';

    private const string CSRF_OPTION = 'csrf_protection';
    private const string CSRF_FIELD = '_token';

    public function __construct(
        protected FormFactoryInterface $formFactory,
        protected FormRegistryInterface $formRegistry,
        protected StaticWidgetConfigRepository $repository,
        protected TokenStorageInterface $tokenStorage,
    ) {
    }

    public function configure(string $widgetKey, WidgetTypeInterface $widget): ConfiguredStaticWidget
    {
        $userIdentifier = $this->getUserIdentifier();
        if (!$this->isConfigurable($widget) || $userIdentifier === null) {
            return new ConfiguredStaticWidget($widget);
        }

        $stored = $this->repository->findOneByUserAndWidget($userIdentifier, $widgetKey);
        $values = $stored ? $this->rehydrate($widgetKey, $widget, $stored->getConfig()) : null;

        return $this->buildConfigured($widgetKey, $widget, $values, $stored !== null);
    }

    public function save(string $widgetKey, WidgetTypeInterface $widget, Request $request): ConfiguredStaticWidget
    {
        $userIdentifier = $this->requireUserIdentifier();
        $form = $this->createForm($widgetKey, $this->requireConfigurable($widget));
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            $current = $this->configure($widgetKey, $widget);

            return new ConfiguredStaticWidget($current->getWidget(), $form->createView(), $current->isCustomized());
        }

        $submittedValues = $request->request->all($form->getName());
        unset($submittedValues[self::CSRF_FIELD]);

        $stored = $this->repository->findOneByUserAndWidget($userIdentifier, $widgetKey)
            ?? new StaticWidgetConfig($userIdentifier, $widgetKey);
        $stored->setConfig($submittedValues);
        $this->repository->save($stored);

        $values = $form->getData();

        return $this->buildConfigured($widgetKey, $widget, is_array($values) ? $values : null, true);
    }

    public function reset(string $widgetKey, WidgetTypeInterface $widget): ConfiguredStaticWidget
    {
        $userIdentifier = $this->requireUserIdentifier();
        $this->requireConfigurable($widget);

        $stored = $this->repository->findOneByUserAndWidget($userIdentifier, $widgetKey);
        if ($stored) {
            $this->repository->remove($stored);
        }

        return $this->buildConfigured($widgetKey, $widget, null, false);
    }

    public function isConfigurable(WidgetTypeInterface $widget): bool
    {
        return $widget instanceof AbstractWidget && $widget->getConfigFormType() !== null;
    }

    /**
     * @param array<string, mixed>|null $values
     */
    protected function buildConfigured(
        string $widgetKey,
        WidgetTypeInterface $widget,
        ?array $values,
        bool $customized,
    ): ConfiguredStaticWidget {
        $abstractWidget = $this->requireConfigurable($widget);
        $configured = clone $abstractWidget;
        if ($values !== null) {
            $configured->setConfig(array_merge($abstractWidget->getConfig() ?? [], $values));
        }

        $form = $this->createForm($widgetKey, $abstractWidget, $values);

        return new ConfiguredStaticWidget($configured, $form->createView(), $customized);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function rehydrate(string $widgetKey, WidgetTypeInterface $widget, array $storedValues): ?array
    {
        $abstractWidget = $this->requireConfigurable($widget);
        /** @var class-string<FormTypeInterface<mixed>> $formType */
        $formType = $abstractWidget->getConfigFormType();

        $options = $abstractWidget->getConfigFormOptions();
        // stored values carry no token: they were already checked when the user submitted them
        if ($this->formRegistry->getType($formType)->getOptionsResolver()->isDefined(self::CSRF_OPTION)) {
            $options[self::CSRF_OPTION] = false;
        }

        $form = $this->formFactory->createNamed(self::FORM_NAME_PREFIX . $widgetKey, $formType, null, $options);
        $form->submit($storedValues);

        $values = $form->getData();

        return $form->isValid() && is_array($values) ? $values : null;
    }

    /**
     * @param array<string, mixed>|null $values
     */
    protected function createForm(string $widgetKey, AbstractWidget $widget, ?array $values = null): FormInterface
    {
        /** @var class-string<FormTypeInterface<mixed>> $formType */
        $formType = $widget->getConfigFormType();

        return $this->formFactory->createNamed(
            self::FORM_NAME_PREFIX . $widgetKey,
            $formType,
            $values,
            $widget->getConfigFormOptions(),
        );
    }

    protected function getUserIdentifier(): ?string
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user?->getUserIdentifier();
    }

    protected function requireUserIdentifier(): string
    {
        return $this->getUserIdentifier()
            ?? throw new AccessDeniedException('Configuring a static widget requires an authenticated user.');
    }

    protected function requireConfigurable(WidgetTypeInterface $widget): AbstractWidget
    {
        if (!$widget instanceof AbstractWidget || !$this->isConfigurable($widget)) {
            throw new \LogicException(sprintf(
                'The widget "%s" has no config form: override AbstractWidget::getConfigFormType() to make it configurable.',
                $widget->getName(),
            ));
        }

        return $widget;
    }
}

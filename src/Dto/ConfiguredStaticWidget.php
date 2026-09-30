<?php

namespace Lle\DashboardBundle\Dto;

use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Symfony\Component\Form\FormView;

class ConfiguredStaticWidget
{
    public function __construct(
        protected WidgetTypeInterface $widget,
        protected ?FormView $form = null,
        protected bool $customized = false,
    ) {
    }

    public function getWidget(): WidgetTypeInterface
    {
        return $this->widget;
    }

    public function getForm(): ?FormView
    {
        return $this->form;
    }

    public function isCustomized(): bool
    {
        return $this->customized;
    }
}

<?php

namespace Lle\DashboardBundle\Widgets;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class PostItWidget extends AbstractWidget
{
    public function render()
    {
        return $this->twig("@LleDashboard/widget/post_it_widget.html.twig");
    }

    public function getName()
    {
        return "widget.postit.title";
    }
}

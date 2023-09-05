<?php

namespace Lle\DashboardBundle\Widgets;

class PostIt extends AbstractWidget
{
    public function render(): string
    {
        return $this->twig("@LleDashboard/widget/post_it_widget.html.twig");
    }

    public function getName(): string
    {
        return "widget.postit.title";
    }
}

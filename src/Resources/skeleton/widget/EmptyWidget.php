<?= "<?php" ?>

namespace <?= $namespace ?>;

use Lle\DashboardBundle\Widgets\AbstractWidget;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Twig\Environment;

class <?= $classname ?> extends AbstractWidget
{
    private Environment $twig;
    private $user;

    public function __construct(Environment $twig, Security $security)
    {
        $this->twig = $twig;
        $this->user = $security->getUser();
    }

    public function render()
    {
        $data = "Hello";
        return $this->twig->render("widget/<?= $widgetname?>.html.twig", [
            "widget" => $this,
            "data" => $data,
        ]);
    }

    public function getName()
    {
        return "widget.<?= $widgetname?>.title";
    }
}

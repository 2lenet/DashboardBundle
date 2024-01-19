<?= "<?php" ?>


namespace <?= $namespace ?>;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Widgets\AbstractWidget;

class <?= $classname ?> extends AbstractWidget
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
    }

    public function render(): string
    {
        $data = "world";

        return $this->twig("widget/<?= strtolower($widgetname) ?>.html.twig", [
            "data" => $data,
        ]);
    }

    public function getName(): string
    {
        return "widget.<?= strtolower($widgetname) ?>.title";
    }
}

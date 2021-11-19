<?= "<?php" ?>


namespace <?= $namespace ?>;

use Doctrine\ORM\EntityManagerInterface;
use Lle\DashboardBundle\Widgets\AbstractWidget;

class <?= $classname ?> extends AbstractWidget
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function render()
    {
        $data = "world";

        return $this->twig("widget/<?= strtolower($widgetname) ?>.html.twig", [
            "data" => $data,
        ]);
    }

    public function getName()
    {
        return "widget.<?= strtolower($widgetname) ?>.title";
    }
}

<?= "<?php" ?>


namespace <?= $namespace ?>;

use App\Entity\<?= $entity ?>;
use App\Repository\<?= $entity ?>Repository;
use Lle\DashboardBundle\Widgets\AbstractWidget;
use Symfony\Component\Workflow\Registry;
use Twig\Environment;

class <?= $classname ?> extends AbstractWidget
{
    protected Environment $twig;
    private <?= $entity ?>Repository $<?= strtolower($entity) ?>Repository;
    private Registry $workflows;

    public function __construct(Environment $twig, <?= $entity ?>Repository $<?= strtolower($entity) ?>Repository, Registry $workflows)
    {
        $this->twig = $twig;
        $this-><?= strtolower($entity) ?>Repository = $<?= strtolower($entity) ?>Repository;
        $this->workflows = $workflows;
        $this->height = 2;
        $this->width = 6;
    }

    public function render(): string
    {
        // All states
        $states = $this->workflows->get(new <?= $entity ?>())->getDefinition()->getPlaces();
        // Or custom states
        // $states = ['state1', 'state2', 'state3'];

        $counts = $this-><?= strtolower($entity) ?>Repository
            ->createQueryBuilder('root')
            ->select('root.<?= $workflow ?>, COUNT(root.id) as count')
            ->groupBy('root.<?= $workflow ?>')
            ->getQuery()->getArrayResult();

        foreach ($states as $index => $state) {
            $stateIndex = array_search($state, array_column($counts, '<?= $workflow ?>'));
            if ($stateIndex === false) {
                $states[$index] = ['<?= $workflow ?>' => $state, 'count' => 0];
            } else {
                $states[$index] = $counts[$stateIndex];
            }
        }

        return $this->twig->render('widget/<?= strtolower($widgetname) ?>.html.twig', [
            'widget' => $this,
            'states' => $states
        ]);
    }

    public function getName(): string
    {
        return "widget.<?= strtolower($widgetname) ?>.title";
    }
}

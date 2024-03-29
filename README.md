This bundle provides a dashboard with customizable widgets.

### Table of contents
* [Installation](#installation)
  * [Creating widgets](#creating-widgets)
* [Recipes](#recipes)
  * [Troubleshooting](#troubleshooting)
  * [Templating](#templating)
  * [Widget configuration](#widget-configuration)
  * [Widget cache](#widget-cache)
  * [Widget roles](#widget-roles)
* [Understand the data structure](#understand-the-data-structure)

# Installation

`composer require 2lenet/dashboard2-bundle`

Add this to routes.yaml
```yaml
dashboard_widgets:
    resource: "@LleDashboardBundle/Resources/config/routes.yaml"
```

You will also need to update your database to have the widgets table.
```
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```
> :warning: Do not forget to check your migration file !

## Creating widgets

### With the maker:

`php bin/console make:widget`

Just provide a short name for your widget and the maker will generate the class and the template for you.

If you want a widget for your workflow, you should use this maker : `php bin/console make:workflow-widget`

### If you prefer to do it yourself :

Create a class that extends `AbstractWidget` and fill in the methods.

```php
use Lle\DashboardBundle\Widgets\AbstractWidget;
```

| Method   | Description |
| ---      | ---      |
| render   | **Mandatory.** Return a string that will be the widget content. |
| getName  | Get the widget title that will appear in the header. It will be translated by default. |
| supports | If this method returns false, the users won't be able to see or add it. |
| supportsAjax | NOT SUPPORTED YET |

# Recipes

## Troubleshooting

Why don't I see my widget ?!
* Check the [roles](#widget-roles).
* Check your network tab; maybe the widget is returning a 500.
* Try to clear the cache.

How do I get the logged user ?!
* Use $this->security->getUser().

Why is the dashboard ugly/not working ?!
* `bin/console asset:install`.

Why do I get a 404 ?
* RTFM. Add the routes as specified above.

When I add a widget, they appear *very* far in the bottom ?!
* The widgets are added below the most bottom existing widget. You may have a widget that does not appear.

*Feel free to add more*

## Templating

A base template exists :
```twig
{% extends '@LleDashboard/widget/base_widget.html.twig' %}
```

To easily render a template, you can use the twig() method. It will automatically add a "widget" variable that contains your type.

Example :
```php
public function render()
{
    return $this->twig("widget/pasta_widget.html.twig", [
        "data" => $data,
    ]);
}
```

Note that base template uses Bootstrap 5 cards. Various blocks exists to override the base template.

If you want to hide the header of a widget, and only show it on hover : you must add the following lines in the template of your widget :
```twig
{% block widget_class %}
    card-simplified
{% endblock %}
```


By default, there is a button to export a widget as PDF. You can remove this feature :

Example :
```php
public function render()
{
    return $this->twig("widget/pasta_widget.html.twig", [
        "data" => $data,
        "exportable" => false
    ]);
}
```

You can define two parameters to configure your export : orientation (portrait or landscape) and format (a4, a3, a2, ...)

Example :
```php
public function render()
{
    return $this->twig("widget/pasta_widget.html.twig", [
        "data" => $data,
        "exportable" => [
            "orientation" => "landscape",
            "format" => "a3"
        ],
    ]);
}
```


## Widget configuration

Each widget is individually configurable. The property "config" in the widgets is a JSON field where you can put anything you like.
By default, this field is used by the configuration form.

If you want to add a configuration form, you can use the createForm() method, which works like the Controller one.
Then, you need to pass the form as a variable named `config_form` to the template.

Example:
```php
public function render()
{
    $form = $this->createForm(InterventionWidgetType::class);
    
    return $this->twig("widget/cake_widget.html.twig", [
        "data" => $data,
        "config_form" => $form->createView()
    ]);
}
```

```php
class InterventionWidgetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('etat', ChoiceType::class, [
                'choices' => $yourChoices
            ])
        ;
    }
    
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
```

The result of the form will overwrite the config property, in a JSON format.

To retrieve your form value in the widget : `$this->getConfig("etat");`

## Widget cache

Widgets are cached for 5 minutes, to avoid doing calculations everytime, especially for big charts.  
The cache is based on a cache key, if the value of the key changes, the cache is refreshed, whether 5 minutes have passed or not.

You can change the timeout and the cache key with the following :

```php
public function getCacheKey(): string
{
    return $this->getId() . "_" .md5($this->config);
}

public function getCacheTimeout(): int
{
    return 300;
}
```

If you want to disable the cache for a widget, just make sure that getCacheTimeout returns 0.

## Widget roles
Widgets have roles on them, generated from the name.  
Example : PostIt => ROLE_DASHBOARD_POST_IT

If you want to change this behaviour, simply override supports(), or add a voter.

## Add/configure a chartJS widget
Once configured, this widget allows the user to obtain a chart based on the application provided charts configuration.
To do this, you need to create the different possible configurations and generate the data accordingly.

First, implements the ChartProviderInterface on your class (Repository, Service, ...).
Then add the `getChart` and `getChartList` methods.

The `getChartList` method is used to list the provided charts configurations usable by the widget
The `getChart` method return a ChartModel ( from symfonyUx Chart bundle ).

#### `getDataConf`:
This method return an array with the differents configurations.
```php
public function getChartList(): array
{
    return [
        'COUNTSOMETHING-DAY-30',
        'COUNTSOMETHING-DAY-60',
        'COUNTSOMETHING-MONTH-12',
        'COUNTSOMETHING-MONTH-24',
        'SUMSOMETHING-DAY-30',
        'SUMSOMETHING-DAY-60',
        'SUMSOMETHING-MONTH-12',
        'SUMSOMETHING-YEAR-1'
    ];
}
```

#### `getChart`:
For this method, you will receive selected chart key.

Next, you need to create/return a chart model (  \Symfony\UX\Chartjs\Model\Chart )


A full example with a table KPI and KPI Value to graph arbitrary datas:
```php
    public function getChartList(): array
    {
        $qb = $this->createQueryBuilder('kv');
        $qb->join("kv.kpi", "k");
        $qb->distinct();
        $qb->select('k.code');
        $codes = [];

        foreach ($qb->getQuery()->execute() as $code) {
            $codes[] = $code["code"];
        }

        return $codes;
    }
    public function getChart(string $confKey): Chart
    {
        $labels = [];
        $values = [];
        foreach ($this->getData($confKey) as $row) {
            $labels[] = $row['date'];
            $values[] = $row['value'];
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $confKey,
                    'backgroundColor' => 'rgb(255, 99, 132, .4)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'data' => $values,
                    'tension' => 0.4,
                ],
            ],
        ]);
        $chart->setOptions([
            'maintainAspectRatio' => false,
        ]);

        return $chart;
    }

    public function getData(string $confKey): array
    {
        $qb = $this->createQueryBuilder('kv');
        $qb->join("kv.kpi", "k");
        $qb->select('SUM(kv.value) as value');
        $qb->where("k.code = :kpi");
        $qb->setParameter("kpi", $confKey);

        $qb
            ->addSelect("CONCAT(WEEK(kv.date), '-', YEAR(kv.date)) as date")
            ->groupBy('date');

        return $qb->getQuery()->getResult();
    }
```


# Understand the data structure

Widget Entity <--> Widget Type <--> DashboardController

A WidgetType (eg. PostItWidget) is simply a *definition* that will be used by the controller.  
When an user adds a widget, it will create a distinct entity.  
A widget may have multiple entities for the same type. For example, an user may have multiple post-its with different contents.

Some widgets do not have an user_id filled in. They are the default widgets, which may only be created by the super admin (using the buttons in the dashboard)

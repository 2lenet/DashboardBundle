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
* [Static dashboard](#static-dashboard)
  * [Setup](#setup)
  * [Widget visibility](#widget-visibility)
  * [Tabs](#tabs)
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


# Static dashboard

The bundle provides a static dashboard mode that displays a fixed list of widgets without any database storage or user customization (no drag, no add/remove, no per-user config).
The list of widgets to render is supplied by a service that the application must implement, and can be displayed on a single screen or [split into tabs](#tabs).

## Setup

### 1. Route

Point your home route (or any route you want) to `StaticDashboardController::staticDashboard`:

```yaml
# config/routes/dashboard.yaml
home:
    path: /
    controller: Lle\DashboardBundle\Controller\StaticDashboardController::staticDashboard
dashboard:
    resource: "@LleDashboardBundle/Resources/config/routes.yaml"
```

The static dashboard is also available out of the box at `/dashboard/static`.

### 2. Implement the static widget provider (mandatory)

The controller does not know which widgets to display: it delegates that to a service implementing `Lle\DashboardBundle\Contracts\StaticWidgetProviderInterface`:

```php
namespace Lle\DashboardBundle\Contracts;

interface StaticWidgetProviderInterface
{
    public function getMyWidgets(): array;

    public function getWidget(string $index): ?WidgetTypeInterface;
}
```

`getMyWidgets()` returns the ordered list of widget instances to render. `getWidget($index)` resolves a single widget by the **array key** used in `getMyWidgets()` — that key is the `staticIndex` passed to the ajax refresh route.

Inject `iterable $widgetTypes` (Symfony tagged iterator) to receive every widget type defined in the project: widgets are auto-tagged with `lle_dashboard.widget` because they implement `WidgetTypeInterface`. Then pick the ones you want to display, optionally overriding their config (title, etc.) via `setConfig()`.

Real example from this project (`src/Service/Dashboard/StaticWidgetProvider.php`):

```php
namespace App\Service\Dashboard;

use App\Widget\DossierWorkflow;
use App\Widget\MonitoringBoxes;
use App\Widget\QuotaSms;
use App\Widget\StatsDayWidget;
use App\Widget\StatsWidget;
use App\Widget\SuiviTelechargement;
use Lle\DashboardBundle\Contracts\StaticWidgetProviderInterface;
use Lle\DashboardBundle\Contracts\WidgetTypeInterface;
use Lle\DashboardBundle\Widgets\AbstractWidget;

class StaticWidgetProvider implements StaticWidgetProviderInterface
{
    /** @var array<string, WidgetTypeInterface> */
    protected array $widgetTypes = [];

    /** @var array<string, WidgetTypeInterface> */
    protected array $widgets = [];

    public function __construct(iterable $widgetTypes)
    {
        /** @var WidgetTypeInterface $widgetType */
        foreach ($widgetTypes as $widgetType) {
            if ($widgetType->getType()) {
                $this->widgetTypes[$widgetType->getType()] = $widgetType;
            }
        }

        $this->widgets = [
            "workflow" => $this->buildWidget(DossierWorkflow::class, ['title' => 'Dossier Workflow']),
            "boxs" => $this->buildWidget(MonitoringBoxes::class, ['title' => 'Monitoring Boxes']),
            "quotaSms" => $this->buildWidget(QuotaSms::class, ['title' => 'Quota SMS']),
            "suivisTelechargement" => $this->buildWidget(SuiviTelechargement::class, ['title' => 'Suivis Téléchargement']),
            "statsDay" => $this->buildWidget(StatsDayWidget::class, ['title' => 'Stats par jours']),
            "statsAnnuelle" => $this->buildWidget(StatsWidget::class, ['title' => 'Stats']),
        ];
    }

    public function getWidgetType(string $widgetType): ?WidgetTypeInterface
    {
        if (array_key_exists($widgetType, $this->widgetTypes)) {
            return clone $this->widgetTypes[$widgetType];
        }

        return null;
    }

    private function buildWidget(string $class, array $config): AbstractWidget
    {
        $type = self::classToType($class);
        $widget = $this->widgetTypes[$type] ?? null;

        if (!$widget instanceof AbstractWidget) {
            throw new \RuntimeException(sprintf('Widget type "%s" is not registered or does not extend AbstractWidget.', $type));
        }

        $clone = clone $widget;
        $clone->setConfig($config);

        return $clone;
    }

    public function getMyWidgets(): array
    {
        return $this->widgets;
    }

    public function getWidget(string $index): ?WidgetTypeInterface
    {
        return $this->widgets[$index] ?? null;
    }

    private static function classToType(string $class): string
    {
        return str_replace('\\', '_', $class) . '_widget';
    }
}
```

A few things worth noting:

- The `buildWidget()` helper centralizes the lookup-clone-configure logic. It returns `AbstractWidget` (not `WidgetTypeInterface`) because `setConfig()` lives on `AbstractWidget` — typing it that way keeps PHPStan happy and lets callers chain `setConfig()` directly.
- It throws a `RuntimeException` if the widget type is missing (typo in the FQCN, widget not tagged, etc.) rather than silently returning `null` and crashing later on `->setConfig()`. Failing fast at construction time gives a clear stack trace instead of a confusing "method on null" error.
- The instances stored in `$this->widgets` are **clones**, so the per-widget `setConfig()` does not mutate the shared widget type registered in the container.
- The array keys (`"workflow"`, `"boxs"`, ...) are stable identifiers used as `staticIndex` by the ajax refresh route. Don't rename them lightly if the dashboard is already in production.
- `setConfig(['title' => '...'])` lets you display the same widget type several times with different titles, without subclassing.

### 3. Declare your provider to the bundle

The bundle exposes a `static_widget_provider` configuration key. Set it to your implementation FQCN: the bundle aliases `StaticWidgetProviderInterface` to that service so the controller can autowire it.

```yaml
# config/packages/lle_dashboard.yaml
lle_dashboard:
    static_widget_provider: App\Service\Dashboard\StaticWidgetProvider
```

The key defaults to `null`: the alias is then simply not registered, so the rest of the bundle (and any project not using the static dashboard) works normally, and only the `/dashboard/static` routes fail — at request time, not at container compile time.

### 4. Widget sizing

In static mode, widgets are laid out in a Bootstrap grid. The default CSS class is computed from `getWidth()` (the GridStack column count maps to `col-md-{width}`).

You can override this per widget by implementing `getStaticCssClass()`. For example, `DossierWorkflow` in this project takes the full row:

```php
// src/Widget/DossierWorkflow.php
public function getStaticCssClass(): string
{
    return 'col-12 col-md-12';
}
```

### 5. Custom static rendering

By default, `renderStatic()` calls `render()`. Override it in your widget to provide a different rendering in static mode:

```php
public function renderStatic(): string
{
    return $this->twig('widget/my_widget_static.html.twig', [
        'data' => $this->getData(),
    ]);
}
```

The ajax refresh route `/dashboard/render_static_widget/{staticIndex}` calls `getWidget($staticIndex)` on your provider, checks the widget's role, then calls `renderStatic()` on it. With the provider above, `staticIndex` is `"workflow"`, `"boxs"`, etc. — the keys of `$this->widgets`.

### 6. Assets

The javascript of the static dashboard (ajax loading of the widgets, tabs) ships in the bundle's compiled
`staticapp.js`, which the template already loads. After upgrading the bundle, refresh the published assets:

```
php bin/console assets:install
```

> :warning: Up to 2.6.x this javascript was inlined in the template. If the published assets are not refreshed, the
> widget cards stay on their loading spinner.

## Widget visibility

The static dashboard applies the same role check as the standard dashboard: a widget whose `supports()` returns
`false` — by default `ROLE_DASHBOARD_<WIDGET_NAME>`, see [Widget roles](#widget-roles) — is not displayed, and its
content cannot be fetched either.

- The dashboard filters the widgets returned by your provider, so you don't have to check any role in `getMyWidgets()`.
- The ajax route `render_static_widget` answers **404** for an unknown key, and **403** for a widget the user is not
  granted.

> :warning: Up to 2.6.x, the static dashboard displayed every widget returned by the provider, whatever its role.
> When you migrate a standard dashboard to a static one — or upgrade from 2.6.x — make sure the groups that must see
> a widget are actually granted its role, otherwise the widget silently disappears from the dashboard.

## Tabs

A static dashboard can be split into tabs. Implement `StaticTabProviderInterface` instead of
`StaticWidgetProviderInterface` — it extends it, so `getMyWidgets()` and `getWidget()` stay exactly the same — and
describe the tabs with `StaticTab` objects:

```php
use Lle\DashboardBundle\Contracts\StaticTabProviderInterface;
use Lle\DashboardBundle\Dto\StaticTab;

class StaticWidgetProvider implements StaticTabProviderInterface
{
    // getMyWidgets() and getWidget() are unchanged

    public function getTabs(): array
    {
        return [
            new StaticTab('monitoring', 'dashboard.tab.monitoring', ['workflow', 'boxs']),
            new StaticTab(
                key: 'sms',
                label: 'dashboard.tab.sms',
                widgetKeys: ['quotaSms'],
                icon: 'fa fa-comment',
                cssClass: 'text-danger',
            ),
        ];
    }
}
```

| Argument | Description |
| --- | --- |
| `key` | Stable identifier of the tab: used in the DOM, and in the url fragment (`#tab-sms`) that reopens the dashboard on that tab. |
| `label` | Title of the tab, translated with the default domain. |
| `widgetKeys` | Keys of the widgets displayed in the tab — the very keys returned by `getMyWidgets()`. |
| `icon` | Optional css classes of an icon displayed before the label, e.g. `fa fa-docker`. |
| `cssClass` | Optional css classes added to the tab itself, e.g. `text-danger`. |

A few things worth noting:

- **Tabs or a single screen, not both.** A tabbed dashboard displays nothing outside of its tabs: every key returned by
  `getMyWidgets()` must be assigned to a tab, otherwise a `LogicException` is thrown — as it is for a tab referencing an
  unknown widget key, or for a duplicated tab key. To keep the single-screen mode, keep implementing
  `StaticWidgetProviderInterface`.
- **A tab whose widgets are all forbidden for the current user is not displayed at all**, so nobody lands on an empty
  tab. A dashboard whose every tab is hidden displays no tab bar.
- **The widgets of a tab are only fetched the first time that tab is displayed.** Besides saving requests, this is what
  lets a widget compute its own width: a table or a chart rendered inside a hidden pane would size itself against a
  zero-width container.
- The tab bar is plain Bootstrap 5 markup (`nav-tabs` + `tab-content`) driven by the bundle's own javascript: there is
  nothing else to load, and the widget templates need no change.

# Understand the data structure

Widget Entity <--> Widget Type <--> DashboardController

A WidgetType (eg. PostItWidget) is simply a *definition* that will be used by the controller.  
When an user adds a widget, it will create a distinct entity.  
A widget may have multiple entities for the same type. For example, an user may have multiple post-its with different contents.

Some widgets do not have an user_id filled in. They are the default widgets, which may only be created by the super admin (using the buttons in the dashboard)

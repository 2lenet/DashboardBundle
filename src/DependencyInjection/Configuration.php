<?php

namespace Lle\DashboardBundle\DependencyInjection;

use Lle\DashboardBundle\Contracts\StaticWidgetProviderInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('lle_dashboard');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('static_widget_provider')
                    ->defaultValue(StaticWidgetProviderInterface::class)
                ->end()
            ->end();

        return $treeBuilder;
    }
}

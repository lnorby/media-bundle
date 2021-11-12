<?php

namespace Lnorby\MediaBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('lnorby_media');
        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('public_path')->defaultValue('/media')->end()
            ->arrayNode('storage')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('local')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('path')->defaultValue('%kernel.project_dir%/public/media')->end()
            ->end()
            ->end()
            ->arrayNode('image')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('width')->defaultValue(1920)->end()
            ->scalarNode('height')->defaultValue(1920)->end()
            ->scalarNode('quality')->defaultValue(70)->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}

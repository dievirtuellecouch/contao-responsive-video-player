<?php

namespace DVC\ResponsiveVideoPlayer\DependencyInjection\Compiler;

use DVC\ResponsiveVideoPlayer\FileVariantProvider\SearchStrategyProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SearchStrategyPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(SearchStrategyProvider::class)) {
            return;
        }

        $definition = $container->findDefinition(SearchStrategyProvider::class);

        $taggedServices = $container->findTaggedServiceIds('dvc.responsive_video_player.search_strategy');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addSearchStrategy', [new Reference($id)]);
        }
    }
}

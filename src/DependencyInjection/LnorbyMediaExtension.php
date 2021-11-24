<?php

namespace Lnorby\MediaBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class LnorbyMediaExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('Lnorby\MediaBundle\Storage\LocalStorage');
        $definition->replaceArgument(0, $config['storage']['local']['path']);

        $definition = $container->getDefinition('Lnorby\MediaBundle\DownloadManager');
        $definition->replaceArgument(0, $config['public_path']);

        $definition = $container->getDefinition('Lnorby\MediaBundle\Uploader\UploadManager');
        $definition->replaceArgument(0, $config['storage']['image']['width']);
        $definition->replaceArgument(1, $config['storage']['image']['height']);
        $definition->replaceArgument(2, $config['storage']['image']['quality']);
    }
}

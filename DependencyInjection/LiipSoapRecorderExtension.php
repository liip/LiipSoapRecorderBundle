<?php

namespace Liip\SoapRecorderBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Load the bundle configuration to a specific container parameter
 *
 * @author David Jeanmonod <david.jeanmonod@liip.ch>
 */
class LiipSoapRecorderExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('liip_soap_recorder_config', $config);
                
        if($config['enable_profiler'] === true) {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
            $loader->load('profiler.xml');
        }
    }
}

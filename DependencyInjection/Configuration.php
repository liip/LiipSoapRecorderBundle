<?php

namespace Liip\SoapRecorderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Define the bundle configuration
 *
 * @author David Jeanmonod <david.jeanmonod@liip.ch>
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $availableFetchingMode = array('remote', 'local_first', 'local_only');
        $treeBuilder->root('soap_recorder')
            ->children()
                ->booleanNode('record')
                    ->defaultFalse()
                ->end()
                ->scalarNode('fetching_mode')
                    ->defaultValue('remote')
                    ->validate()
                        ->ifNotInArray($availableFetchingMode)
                        ->thenInvalid('Invalid fetching mode [%s], must be one of ['.implode(', ', $availableFetchingMode).']')
                    ->end()
                ->end()
                ->scalarNode('request_folder')
                    ->defaultNull()
                ->end()
                ->scalarNode('response_folder')
                    ->defaultNull()
                ->end()
                ->scalarNode('wsdl_folder')
                    ->defaultNull()
                ->end()        
                ->booleanNode('enable_profiler')
                    ->defaultFalse()
                ->end()                      
            ->end()
        ->end();
        return $treeBuilder;
    }
}

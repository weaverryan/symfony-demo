<?php

namespace Symfony\Bundle\SecurityBundle\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Ryan Weaver <weaverryan@gmail.com>
 */
class GuardAuthenticationFactory implements SecurityFactoryInterface
{
    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'guard';
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('provider')->end()
                ->scalarNode('authenticator')->cannotBeEmpty()->end()
                ->booleanNode('remember_me')->defaultFalse()->end()
            ->end()
        ;
    }

    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        // configure the GuardAuthenticationFactory to have the dynamic constructor arguments
        $providerId = 'security.authentication.provider.guard.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('security.authentication.provider.guard'))
            ->replaceArgument(0, new Reference($config['authenticator']))
            ->replaceArgument(1, new Reference($userProvider))
            ->replaceArgument(2, $id)
        ;

        // listener
        $listenerId = 'security.authentication.listener.guard.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('security.authentication.listener.guard'));
        $listener->replaceArgument(2, $id);
        $listener->replaceArgument(3, new Reference($config['authenticator']));

        if ($config['remember_me']) {
            $container
                ->getDefinition($listenerId)
                ->addTag('security.remember_me_aware', array('id' => $id, 'provider' => $userProvider));
        }

        // providerId, listenerId and entryPointId
        return array($providerId, $listenerId, null);
    }
}

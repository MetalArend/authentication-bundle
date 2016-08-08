<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\DependencyInjection\Reference;

class ShibbolethAuthenticationListenerFactory implements SecurityFactoryInterface
{
    protected $key = 'kuleuven_authentication';

    public function getKey()
    {
        return $this->key;
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
            ->scalarNode('provider')->defaultValue('kuleuven_authentication.service.shibboleth_user_provider')->end()
            ->arrayNode('default_roles')->prototype('scalar')->end()->defaultValue([])->end()
            ->end();
    }

    protected function createAuthenticationProvider(ContainerBuilder $container, $id, $userProvider)
    {
        $providerId = 'security.authentication.provider.' . $this->key . '.' . $id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator($this->key . '.security.authentication.provider'))
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(2, $this->key);
        return $providerId;
    }

    protected function createEntryPoint(ContainerBuilder $container, $id, $defaultEntryPoint)
    {
        if (null !== $defaultEntryPoint) {
            return $defaultEntryPoint;
        }
        $entryPointId = 'security.authentication.entry_point.' . $this->key . '.' . $id;
        $container->setDefinition($entryPointId, new DefinitionDecorator($this->key . '.security.authentication.entry_point'));
        return $entryPointId;
    }

    protected function createListener(ContainerBuilder $container, $id, $defaultRoles)
    {
        $listenerId = 'security.authentication.listener.' . $this->key . '.' . $id;
        $container
            ->setDefinition($listenerId, new DefinitionDecorator($this->key . '.security.authentication.listener'))
            ->replaceArgument(5, $defaultRoles)
            ->replaceArgument(6, $this->key);

        return $listenerId;
    }

    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = $this->createAuthenticationProvider($container, $id, $userProvider);
        $entryPointId = $this->createEntryPoint($container, $id, $defaultEntryPoint);
        $listenerId = $this->createListener($container, $id, $config['default_roles']);

        return [$providerId, $listenerId, $entryPointId];
    }
}

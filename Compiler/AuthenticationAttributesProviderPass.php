<?php

namespace Kuleuven\AuthenticationBundle\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AuthenticationAttributesProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('kuleuven_authentication.service.shibboleth_attributes_injector_manager')) {
            return;
        }

        $definition = $container->findDefinition('kuleuven_authentication.service.shibboleth_attributes_injector_manager');

        $taggedServices = $container->findTaggedServiceIds('kuleuven_authentication.shibboleth_attributes_injector');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                $definition->addMethodCall('addProvider', [
                    new Reference($id),
                    isset($attributes['priority']) ? $attributes['priority'] : 0,
                ]);
            }
        }
    }
}

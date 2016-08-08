<?php

namespace Kuleuven\AuthenticationBundle;

use Kuleuven\AuthenticationBundle\Compiler\AuthenticationAttributesProviderPass;
use Kuleuven\AuthenticationBundle\Security\ShibbolethAuthenticationListenerFactory;
//use Kuleuven\AuthenticationBundle\Security\ShibbolethSwitchUserListenerFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KuleuvenAuthenticationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new AuthenticationAttributesProviderPass());

        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
//        $extension->addSecurityListenerFactory(new ShibbolethSwitchUserListenerFactory());
        $extension->addSecurityListenerFactory(new ShibbolethAuthenticationListenerFactory());
    }
}

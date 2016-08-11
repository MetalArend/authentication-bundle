<?php

namespace Kuleuven\AuthenticationBundle;

use Kuleuven\AuthenticationBundle\Compiler\AuthenticationAttributesProviderPass;
use Kuleuven\AuthenticationBundle\Compiler\KuleuvenShibbolethAttributeDefinitionsXmlParserPass;
use Kuleuven\AuthenticationBundle\Security\ShibbolethAuthenticationListenerFactory;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class KuleuvenAuthenticationBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $bundleDirectory = dirname((new \ReflectionClass(get_class($this)))->getFileName());
        $xmlPath = $bundleDirectory . '/Resources/xml/attribute-map.xml';
        $container->addCompilerPass(new KuleuvenShibbolethAttributeDefinitionsXmlParserPass($xmlPath));
        $container->addCompilerPass(new AuthenticationAttributesProviderPass());


        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new ShibbolethAuthenticationListenerFactory());
    }
}

<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall;

class ShibbolethAttributesInjectionProviderManager
{
    /**
     * @var AttributesProviderInterface[]|ArrayCollection
     */
    protected $providerPropertiesCollection;

    /**
     * @var AttributeDefinitionsProviderInterface
     */
    protected $attributeDefinitionsProvider;

    /**
     * @param AttributeDefinitionsProviderInterface $attributeDefinitionsProvider
     */
    public function __construct(AttributeDefinitionsProviderInterface $attributeDefinitionsProvider)
    {
        $this->attributeDefinitionsProvider = $attributeDefinitionsProvider;
        $this->providerPropertiesCollection = new ArrayCollection();
    }

    /**
     * @param AttributesInjectionProviderInterface $provider
     * @param int                                  $priority
     */
    public function addProvider(AttributesInjectionProviderInterface $provider, $priority = 0)
    {
        if ($provider instanceof ParameterAttributesProvider && 0 === $priority) {
            $priority = -INF;
        }
        $this->providerPropertiesCollection->add(['priority' => $priority, 'provider' => $provider]);
    }

    /**
     * @inheritdoc
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!empty($this->providerPropertiesCollection)) {
            $attributeDefinitions = $this->attributeDefinitionsProvider->getAttributeDefinitions();
            $lcIdOrAliasMap = [];
            foreach ($attributeDefinitions as $idOrAlias => $attributeDefinition) {
                $lcIdOrAliasMap[strtolower($idOrAlias)] = $idOrAlias;
            }
            $server = $event->getRequest()->server;
            $providerPropertiesCollectionIterator = $this->providerPropertiesCollection->getIterator();
            $providerPropertiesCollectionIterator->uasort(function ($first, $second) {
                // Place highest priority first
                if ($first['priority'] === $second['priority']) {
                    return 0;
                }
                return (int)$first['priority'] > (int)$second['priority'] ? -1 : 1;
            });
            foreach ($providerPropertiesCollectionIterator as $providerProperties) {
                /** @var AttributesInjectionProviderInterface $provider */
                $provider = $providerProperties['provider'];
                if (!$provider->isEnabled()) {
                    continue;
                }
                $attributes = $provider->getAttributes();
                foreach ($attributes as $name => $value) {
                    $attributeDefinition = null;
                    switch (true) {
                        case isset($attributeDefinitions[$name]):
                            $attributeDefinition = $attributeDefinitions[$name];
                            break;
                        case isset($lcIdOrAliasMap[$name], $attributeDefinitions[$lcIdOrAliasMap[$name]]):
                            $attributeDefinition = $attributeDefinitions[$lcIdOrAliasMap[$name]];
                            break;
                        default:
                            continue 2; // switch is considered a looping structure, we have to continue the foreach
                    }
                    $id = $attributeDefinition['id'];
                    $aliases = $attributeDefinition['aliases'];
                    $server->set($id, (string)$value);
                    foreach ($aliases as $alias) {
                        $server->set($alias, (string)$value);
                    }
                }
            }
        }
    }
}

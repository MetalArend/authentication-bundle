<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall;

class AttributesInjectionProviderManager
{
    /**
     * @var AttributesInjectionProviderInterface[]|ArrayCollection
     */
    protected $injectorPropertiesCollection;

    /**
     *
     */
    public function __construct()
    {
        $this->injectorPropertiesCollection = new ArrayCollection();
    }

    /**
     * @param AttributesInjectionProviderInterface $injector
     * @param int                                  $priority
     */
    public function addProvider(AttributesInjectionProviderInterface $injector, $priority = 0)
    {
        if ($injector instanceof ParameterAttributesProvider && 0 === $priority) {
            $priority = -INF;
        }
        $this->injectorPropertiesCollection->add(['priority' => $priority, 'injector' => $injector]);
    }

    /**
     * @inheritdoc
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if (!empty($this->injectorPropertiesCollection)) {
            $server = $event->getRequest()->server;
            $injectorPropertiesCollectionIterator = $this->injectorPropertiesCollection->getIterator();
            $injectorPropertiesCollectionIterator->uasort(function ($first, $second) {
                // Place highest priority first
                if ($first['priority'] === $second['priority']) {
                    return 0;
                }
                return (int)$first['priority'] > (int)$second['priority'] ? -1 : 1;
            });
            foreach ($injectorPropertiesCollectionIterator as $injectorData) {
                /** @var AttributesInjectionProviderInterface $injector */
                $injector = $injectorData['injector'];
                $attributes = $injector->getInjectionAttributes();
                foreach ($attributes as $idOrAlias => $value) {
                    $server->set($idOrAlias, $value);
                }
            }
        }
    }
}

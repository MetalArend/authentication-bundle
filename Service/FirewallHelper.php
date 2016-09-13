<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\FirewallMapInterface;

class FirewallHelper
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var FirewallMapInterface
     */
    protected $firewallMap;

    /**
     * @param RequestStack         $requestStack
     * @param FirewallMapInterface $firewallMap
     */
    public function __construct(RequestStack $requestStack, FirewallMapInterface $firewallMap)
    {
        $this->requestStack = $requestStack;
        $this->firewallMap = $firewallMap;
    }

    /**
     * @param $class
     * @return bool
     */
    public function isProtectedBy($class)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (empty($request)) {
            throw new \RuntimeException('No request available');
        }
        $listenersArray = $this->firewallMap->getListeners($request);
        foreach ($listenersArray[0] as $listener) {
            if ($class === get_class($listener)) {
                return true;
            }
        }
        return false;
    }
}

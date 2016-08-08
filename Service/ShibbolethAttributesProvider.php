<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class ShibbolethAttributesProvider implements AttributesProviderInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var array
     */
    protected $attributeDefinitions;

    /**
     * @param RequestStack $requestStack
     * @param array        $attributeDefinitions
     */
    public function __construct(RequestStack $requestStack, array $attributeDefinitions)
    {
        $this->requestStack = $requestStack;
        $this->attributeDefinitions = $attributeDefinitions;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $request = $this->requestStack->getCurrentRequest();
        $attributes = [];
        foreach ($this->attributeDefinitions as $idOrAlias => $attributeDefinition) {
            $attributes[$idOrAlias] = $request->server->get($idOrAlias, null);
        }
        return $attributes;
    }

    /**
     * @return array
     */
    public function getInjectionAttributes()
    {
        return $this->getAttributes();
    }
}

<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\HttpKernel\KernelInterface;

class AttributeDefinitionsProvider implements AttributeDefinitionsProviderInterface
{
    /**
     * @var array
     */
    protected $attributeDefinitions;

    /**
     * @var array
     */
    protected $kuleuvenAttributeDefinitions;

    /**
     * @var array
     */
    protected $attributeDefinitionsCache;

    /**
     * @param array $attributeDefinitions
     * @param array $kuleuvenAttributeDefinitions
     */
    public function __construct($attributeDefinitions, array $kuleuvenAttributeDefinitions = [])
    {
        $this->attributeDefinitions = $attributeDefinitions;
        $this->kuleuvenAttributeDefinitions = $kuleuvenAttributeDefinitions;
    }

    /**
     * @return array
     */
    public function getAttributeDefinitions()
    {
        if (empty($this->attributeDefinitionsCache)) {
            $attributeDefinitions = $this->kuleuvenAttributeDefinitions;

            foreach ($this->attributeDefinitions as $attributeDefinition) {
                $id = $attributeDefinition['id'];
                $aliases = isset($attributeDefinition['aliases']) ? $attributeDefinition['aliases'] : [];
                if (isset($attributeDefinitions[$id])) {
                    // Remove already present aliases
                    foreach ($attributeDefinitions['aliases'] as $alias) {
                        unset($attributeDefinitions[$alias]);
                    }
                }
                $attributeDefinitions[$id] = $attributeDefinition;
                foreach ($aliases as $alias) {
                    $attributeDefinitions[$alias] =& $attributeDefinitions[$id];
                }
            }

            $this->attributeDefinitionsCache = $attributeDefinitions;
        }

        return $this->attributeDefinitionsCache;
    }
}

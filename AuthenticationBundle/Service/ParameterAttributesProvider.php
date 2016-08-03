<?php

namespace Kuleuven\AuthenticationBundle\Service;

class ParameterAttributesProvider implements AttributesInjectionProviderInterface, AttributesProviderInterface
{
    /**
     * @var array
     */
    protected $overrides;

    /**
     * @var array
     */
    protected $attributeDefinitions;

    /**
     * @param array $overrides
     * @param array $attributeDefinitions
     */
    public function __construct($overrides = [], array $attributeDefinitions)
    {
        $this->overrides = $overrides;
        $this->attributeDefinitions = $attributeDefinitions;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        $attributes = [];
        foreach ($this->overrides as $idOrAlias => $value) {
            if (isset($this->attributeDefinitions[$idOrAlias])) {
                $attributeDefinition = $this->attributeDefinitions[$idOrAlias];
                if (!empty($attributeDefinition)) {
                    $attributes[$attributeDefinition['id']] = $value;
                    foreach ($attributeDefinition['aliases'] as $alias) {
                        $attributes[$alias] = $value;
                    }
                } else {
                    $attributes[$idOrAlias] = $value;
                }
            }
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
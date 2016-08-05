<?php

namespace Kuleuven\AuthenticationBundle\Service;

class ParameterAttributesProvider implements AttributesProviderInterface
{
    /**
     * @var array
     */
    protected $overwrites;

    /**
     * @param array $overwrites
     */
    public function __construct($overwrites = [])
    {
        $this->overwrites = $overwrites;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->overwrites;
    }
}
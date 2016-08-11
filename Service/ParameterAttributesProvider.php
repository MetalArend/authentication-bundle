<?php

namespace Kuleuven\AuthenticationBundle\Service;

class ParameterAttributesProvider implements AttributesInjectionProviderInterface
{
    /**
     * @var array
     */
    protected $overwrites;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @param array $overwrites
     * @param bool  $enabled
     */
    public function __construct($overwrites = [], $enabled = false)
    {
        $this->overwrites = $overwrites;
        $this->enabled = $enabled;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->overwrites;
    }
}

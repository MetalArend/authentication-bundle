<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class HeaderAttributesProvider implements AttributesInjectionProviderInterface
{
    /**
     * @var array
     */
    protected $requestStack;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @param RequestStack $requestStack
     * @param bool         $enabled
     */
    public function __construct(RequestStack $requestStack, $enabled = false)
    {
        $this->requestStack = $requestStack;
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
        $attributes = $this->requestStack->getCurrentRequest()->headers->all();
        array_walk($attributes, function (&$value) {
            $value = (is_array($value) ? implode(';', $value) : $value);
        });
        return $attributes;
    }
}

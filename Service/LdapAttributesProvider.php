<?php

namespace Kuleuven\AuthenticationBundle\Service;

class LdapAttributesProvider implements AttributesProviderInterface, AttributesInjectionProviderInterface
{
    /**
     * @var LdapService
     */
    protected $ldapService;

    /**
     * @var array
     */
    protected $filter;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @param LdapService $ldapService
     * @param array       $filter
     * @param bool        $enabled
     */
    public function __construct(LdapService $ldapService, $filter = [], $enabled = false)
    {
        $this->ldapService = $ldapService;
        $this->filter = (!empty($filter) ? $filter : []);
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
     * @param array $filter
     * @param array $attributes
     * @param int   $limit
     * @return array
     * @throws \Exception
     */
    public function getAttributesByFilter(array $filter, array $attributes = [], $limit = 1)
    {
        if (empty($filter)) {
            return [];
        }

        $ldapResults = $this->ldapService->search($filter, $attributes, $limit, false);
        if (0 === $ldapResults['count']) {
            return [];
        }

        $ldapResult = $ldapResults['0'];

        $attributes = [];
        for ($i = 0; $i < $ldapResult['count']; $i++) {
            $name = $ldapResult[$i];
            if (1 === $ldapResult[$name]['count']) {
                $value = $ldapResult[$name][0];
            } else {
                $value = [];
                for ($j = 0; $j < $ldapResult[$name]['count']; $j++) {
                    $value[] = $ldapResult[$name][$j];
                }
                $value = implode(';', $value);
            }
            $attributes[$name] = $value;
        }

        return $attributes;
    }

    /**
     * @param array $attributes
     * @param int   $limit
     * @return array
     */
    public function getAttributes(array $attributes = [], $limit = 1)
    {
        return $this->getAttributesByFilter($this->filter, $attributes, $limit);
    }
}
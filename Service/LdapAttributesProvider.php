<?php

namespace Kuleuven\AuthenticationBundle\Service;

class LdapAttributesProvider implements AttributesInjectionProviderInterface, AttributesProviderInterface
{
    /**
     * @var LdapService
     */
    protected $ldapService;

    /**
     * @var array
     */
    protected $ldapFilter;

    /**
     * @var array
     */
    protected $attributeDefinitions;

    /**
     * @param LdapService $ldapService
     * @param array       $ldapFilter
     * @param array       $attributeDefinitions
     */
    public function __construct(LdapService $ldapService, $ldapFilter = [], array $attributeDefinitions)
    {
        $this->ldapService = $ldapService;
        $this->ldapFilter = (!empty($ldapFilter) ? $ldapFilter : []);
        $this->attributeDefinitions = $attributeDefinitions;
    }

    /**
     * @param array $filter
     * @param array $attributes
     * @param int   $limit
     * @return array
     * @throws \Exception
     */
    protected function getAttributesByFilter(array $filter, array $attributes = [], $limit = 1)
    {
        if (empty($filter)) {
            return [];
        }

        $ldapResults = $this->ldapService->search($filter, $attributes, $limit, false);
        if (0 === $ldapResults['count']) {
            return [];
        }

        $ldapResult = $ldapResults['0'];

        $normalizedResult = [];
        for ($i = 0; $i < $ldapResult['count']; $i++) {
            $name = $ldapResult[$i];
            $normalizedResult[$name] = $ldapResult[$name];
        }

        $attributes = [];
        foreach ($this->attributeDefinitions as $idOrAlias => $attributeDefinition) {
            $name = strtolower($idOrAlias);
            if (!isset($normalizedResult[$name])) {
                continue;
            }
            $value = null;
            if (!$attributeDefinition['multivalue']) {
                $value = $normalizedResult[$name][0];
            } else {
                $value = [];
                for ($j = 0; $j < $normalizedResult[$name]['count']; $j++) {
                    $value[] = $normalizedResult[$name][$j];
                }
                $value = implode(';', $value);
            }
            $attributes[$attributeDefinition['id']] = $value;
            foreach ($attributeDefinition['aliases'] as $alias) {
                $attributes[$alias] = $value;
            }
        }

        return $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->getAttributesByFilter($this->ldapFilter);
    }

    /**
     * @return array
     */
    public function getInjectionAttributes()
    {
        return $this->getAttributes();
    }

    /**
     * @param $uid
     * @return array
     */
    public function getAttributesByUid($uid)
    {
        return $this->getAttributesByFilter(['uid' => $uid]);
    }
}
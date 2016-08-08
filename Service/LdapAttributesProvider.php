<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

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
     * @var SessionInterface
     */
    protected $session;

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
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
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

        // Retrieve from the session (cache)
        $hash = 'shibboleth-authentication-' . md5(serialize($filter) . '-' . serialize($attributes) . '-' . $limit);
        if (!empty($this->session) && $this->session->has($hash)) {
            $ldapResults = $this->session->get($hash);
        } else {
            $ldapResults = $this->ldapService->search($filter, $attributes, $limit, false);
            if (!empty($this->session) && isset($ldapResults['count']) && 0 !== $ldapResults['count']) {
                // Save to the cache
                $this->session->set($hash, $ldapResults);
            }
        }

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

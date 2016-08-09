<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Ldap\Entry;

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
     * @return array
     */
    public function getAttributesByFilter(array $filter)
    {
        // Return early if filter is empty
        if (empty($filter)) {
            return [];
        }

        // Get attributes
        $hash = 'kuleuven-authentication-ldap-results-' . md5(serialize($filter));
        if (!empty($this->session) && $this->session->has($hash)) {
            // Retrieve from the session (cache)
            $attributes = $this->session->get($hash);
        } else {
            // Search LDAP
            $ldapResults = $this->ldapService->search($filter);
            // Return empty array if filter returns more than one user
            if (1 !== $ldapResults->count()) {
                return [];
            }
            // Get the first result
            /** @var Entry $ldapSingleResult */
            $ldapSingleResult = $ldapResults->toArray()['0'];
            // Extract attributes as strings
            $attributes = $ldapSingleResult->getAttributes();
            array_walk($attributes, function (&$value) {
                $value = (is_array($value) ? implode(';', $value) : $value);
            });
            // Save to the session (cache)
            if (!empty($this->session)) {
                $this->session->set($hash, $attributes);
            }
        }

        // Return attributes
        return $attributes;
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        return $this->getAttributesByFilter($this->filter);
    }
}

<?php

namespace Kuleuven\AuthenticationBundle\Service;

class LdapService
{
    protected $rdn;
    protected $password;
    protected $base;
    protected $domain;
    protected $port;
    protected $handle;
    protected $bind;

    public function __construct(
        $rdn = '',
        $password = '',
        $base = 'ou=people,dc=kuleuven,dc=be',
        $domain = 'ldap.kuleuven.be',
        $port = 389,
        $ssl = false
    )
    {
        $this->rdn = $rdn;
        $this->password = $password;
        $this->base = $base;
        if (false === strpos($domain, '://')) {
            $domain = ($ssl ? 'ldaps://' : 'ldap://') . $domain;
        }
        $this->domain = $domain;
        $this->port = $port;

        if (!defined('LDAP_OPT_DIAGNOSTIC_MESSAGE')) {
            define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
        }
    }

    public function __destruct()
    {
        $this->unbind();
    }

    protected function connect()
    {
        if (empty($this->handle)) {
            $this->handle = \ldap_connect($this->domain, $this->port);
            if (false === $this->handle) {
                throw new \Exception('Could not connect to LDAP server: ' . $this->error());
            }
            \ldap_set_option($this->handle, LDAP_OPT_PROTOCOL_VERSION, 3);
            \ldap_set_option($this->handle, LDAP_OPT_REFERRALS, 0);
        }
        return $this;
    }

    protected function bind()
    {
        $this->connect();
        if (empty($this->bind)) {
            $this->bind = @\ldap_bind($this->handle, $this->rdn, $this->password);
            if (false === $this->bind) {
                throw new \Exception('Could not bind to LDAP server: ' . $this->error());
            }
        }
        return $this;
    }

    protected function unbind()
    {
        if (!empty($this->handle)) {
            \ldap_unbind($this->handle);
        }
        return $this;
    }

    public function createFilter(array $data = [], $type = '&', $wrap = '%s')
    {
        $string = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $string .= $this->createFilter($value, $key, $wrap);
            } else {
                $value = sprintf($wrap, $value);
                $string .= "(${key}=${value})";
            }
        }
        return "(${type}${string})";
    }

    public function search($filter, $attributes = ['uid', 'sn', 'givenname', 'mail', 'ou'], $limit = 100, $fuzzy = false)
    {
        if (empty($filter)) {
            return null;
        }

        // Bind
        $this->bind();

        // Add fuzzy
        $wrap = $fuzzy;
        if (is_bool($wrap)) {
            $wrap = $fuzzy ? '*%s*' : '%s';
        }
        if (is_array($filter)) {
            $filter = $this->createFilter($filter, '&', $wrap);
        }

        // Catch empty attributes
        if (empty($attributes)) {
            $attributes = [];
        }

        // Search
        $results = false;
        if ($this->bind) {
            $search = @\ldap_search($this->handle, $this->base, $filter, $attributes, 0, $limit); // add @ to suppress ldap warning
            if (empty($search)) {
                throw new \Exception('Could not search LDAP: ' . $this->error());
            }
            $results = \ldap_get_entries($this->handle, $search);
        }

        // Return
        return $results;
    }

    public function errno()
    {
        $this->bind();

        return \ldap_errno($this->handle);
    }

    public function error()
    {
        $this->bind();

        if (\ldap_get_option($this->handle, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
            return $extended_error;
        }
        return \ldap_error($this->handle);
    }
}

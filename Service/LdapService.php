<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\Ldap\Ldap;

class LdapService
{
    protected $rdn;
    protected $password;
    protected $base;

    public function __construct(
        Ldap $ldap,
        $rdn = '',
        $password = '',
        $base = 'ou=people,dc=kuleuven,dc=be'
    )
    {
        $this->ldap = $ldap;
        $this->rdn = $rdn;
        $this->password = $password;
        $this->base = $base;
    }

    protected function bind()
    {
        $this->ldap->bind($this->rdn, $this->password);
    }

    public function createFilter(array $data = [], $type = '&', $format = '%s')
    {
        $string = '';
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (0 === count(array_filter(array_keys($value), 'is_numeric'))) {
                    $string .= $this->createFilter($value, $key, $format);
                } else {
                    $string .= '(|(' . $key . '=' . implode(')(' . $key . '=', $value) . '))';
                }
            } else {
                $value = sprintf($format, $value);
                $string .= "(${key}=${value})";
            }
        }
        return "(${type}${string})";
    }

    public function search($filter, $attributes = [], $attrsOnly = false, $sizeLimit = 0, $timeLimit = 0)
    {
        // Create filter
        if (is_array($filter)) {
            $filter = $this->createFilter($filter, '&', '%s');
        }

        // Catch empty attributes
        if (empty($attributes)) {
            $attributes = [];
        }

        // Bind
        $this->bind();

        // Search
        $results = $this->ldap->query($this->base, $filter, [
            'attrsOnly' => $attrsOnly,
            'filter'    => $attributes,
//            'maxItems'  => $maxItems,
            'sizeLimit' => $sizeLimit,
            'timeout'   => $timeLimit,
        ])->execute();

        // Return
        return $results;
    }

    public function fuzzy($filter, $attributes = [], $attrsOnly = false, $sizeLimit = 0, $timeLimit = 0)
    {
        if (is_array($filter)) {
            $filter = $this->createFilter($filter, '&', '*%s*');
        }
        return $this->search($filter, $attributes, $attrsOnly, $sizeLimit, $timeLimit);
    }
}

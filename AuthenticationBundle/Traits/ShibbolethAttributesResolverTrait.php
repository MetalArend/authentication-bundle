<?php

namespace Kuleuven\AuthenticationBundle\Traits;

trait ShibbolethAttributesResolverTrait
{
    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * Returns the attributes.
     *
     * @return array The attributes
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Sets the attributes.
     *
     * @param array $attributes The attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Returns true if the attribute exists.
     *
     * @param string $name The attribute name
     * @return bool true if the attribute exists, false otherwise
     */
    public function hasAttribute($name)
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * Returns an attribute value.
     *
     * @param string $name The attribute name
     * @return mixed The attribute value
     * @throws \InvalidArgumentException When attribute doesn't exist
     */
    public function getAttribute($name)
    {
        if (!array_key_exists($name, $this->attributes)) {
            return null;
        }

        return $this->attributes[$name];
    }

    /**
     * Sets an attribute.
     *
     * @param string $name  The attribute name
     * @param mixed  $value The attribute value
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Returns attribute value. If it's a multivalue, the first value is returned, or the value at the specified index.
     *
     * @param string $name
     * @param null   $index
     * @return mixed
     */
    public function getSingleAttribute($name, $index = null)
    {
        $value = $this->getAttribute($name);
        if (!is_array($value)) {
            return $value;
        }
        return (null === $index ? reset($value) : $value[$index]);
    }

    /**
     * Returns an attribute as an array of values.
     *
     * @param string $name
     * @return array
     */
    public function getArrayAttribute($name)
    {
        $value = $this->getAttribute($name);
        return (is_array($value) ? $value : [$value]);
    }

    /**
     * Returns true if attribute exists (if value is given, it will also check the value).
     *
     * @param string      $name
     * @param null|string $value
     * @return bool
     */
    function hasAttributeValue($name, $value = null)
    {
        if (!$this->hasAttribute($name)) return false;
        return (empty($value) ? true : (array_search($value, $this->getArrayAttribute($name)) !== false));
    }

    /**
     * @return string uid
     */
    function getUID()
    {
        return $this->getAttribute('Shib-Person-uid');
    }

    /**
     * Alias for cn
     *
     * @return string cn
     */
    function getCommonName()
    {
        return $this->getAttribute('Shib-Person-commonName');
    }

    /**
     * Alias for cn
     *
     * @return string cn
     */
    function getFullName()
    {
        return $this->getCommonName();
    }

    /**
     * @return string givenName
     */
    function getGivenName()
    {
        return $this->getAttribute('Shib-Person-givenName');
    }

    /**
     * Alias for givenName
     *
     * @return string givenName
     */
    function getFirstName()
    {
        return $this->getGivenName();
    }

    /**
     * @return string sn
     */
    function getSurname()
    {
        return $this->getAttribute('Shib-Person-surname');
    }

    /**
     * Alias for sn
     *
     * @return string sn
     */
    function getLastName()
    {
        return $this->getSurname();
    }

    /**
     * Alias for cn, fallback to uid
     *
     * @return string cn|uid
     */
    function getDisplayName()
    {
        return ($this->hasAttribute('Shib-Person-commonName')) ? $this->getCommonName() : $this->getUsername();
    }

    /**
     * @return string mail
     */
    function getMail()
    {
        return $this->getAttribute('Shib-Person-mail');
    }

    /**
     * Alias for mail
     *
     * @return string mail
     */
    function getEmail()
    {
        return $this->getMail();
    }

    /**
     * @return string mail
     */
    function getMails()
    {
        return $this->getArrayAttribute('Shib-Person-mail');
    }

    /**
     * @return string affiliation
     */
    function getAffiliation()
    {
        return $this->getAttribute('Shib-EP-UnscopedAffiliation');
    }

    /**
     * @return string scopedAffiliation
     */
    function getScopedAffiliation()
    {
        return $this->getAttribute('Shib-EP-ScopedAffiliation');
    }

    /**
     * @param null|string $value
     * @return bool
     */
    function hasAffiliation($value = null)
    {
        return $this->hasAttributeValue('Shib-EP-UnscopedAffiliation', $value);
    }

    /**
     * @param null|string $value
     * @return bool
     */
    function hasScopedAffiliation($value = null)
    {
        return $this->hasAttributeValue('Shib-EP-ScopedAffiliation', $value);
    }

    /**
     * @param null|string $scope
     * @return bool
     */
    function isMember($scope = null)
    {
        return (empty($scope) ? $this->hasAffiliation('member') : $this->hasScopedAffiliation('member@' . $scope));
    }

    /**
     * @param null|string $scope
     * @return bool
     */
    function isEmployee($scope = null)
    {
        return (empty($scope) ? $this->hasAffiliation('employee') : $this->hasScopedAffiliation('employee@' . $scope));
    }

    /**
     * @param null|string $scope
     * @return bool
     */
    function isStudent($scope = null)
    {
        return (empty($scope) ? $this->hasAffiliation('student') : $this->hasScopedAffiliation('student@' . $scope));
    }

    /**
     * @param null|string $scope
     * @return bool
     */
    function isStaff($scope = null)
    {
        return (empty($scope) ? $this->hasAffiliation('staff') : $this->hasScopedAffiliation('staff@' . $scope));
    }

    /**
     * @param null|string $scope
     * @return bool
     */
    function isFaculty($scope = null)
    {
        return (empty($scope) ? $this->hasAffiliation('faculty') : $this->hasScopedAffiliation('faculty@' . $scope));
    }

    /**
     * @return string logoutURL
     */
    function getLogoutURL()
    {
        return $this->getAttribute('Shib-logoutURL');
    }
}
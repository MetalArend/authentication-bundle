<?php

namespace Kuleuven\AuthenticationBundle\Model;

use Symfony\Component\Security\Core\User\UserInterface;

interface KuleuvenUserInterface extends UserInterface
{
    /**
     * Return all attributes.
     */
    public function getAttributes();

    /**
     * Returns true if the attribute exists.
     *
     * @param string $name The attribute name
     * @return bool true if the attribute exists, false otherwise
     */
    public function hasAttribute($name);

    /**
     * Returns an attribute value.
     *
     * @param string $name The attribute name
     */
    public function getAttribute($name);

    /**
     * Returns true if attribute exists (if value is given, it will also check the value).
     *
     * @param string      $name
     * @param null|string $value
     * @return bool
     */
    public function hasAttributeValue($name, $value = null);

    /**
     * @return string cn
     */
    function getCommonName();

    /**
     * @return string givenName
     */
    function getGivenName();

    /**
     * @return string sn
     */
    function getSurname();

    /**
     * Alias for cn, fallback to uid
     *
     * @return string cn|uid
     */
    function getDisplayName();

    /**
     * @return string mail (single)
     */
    function getEmail();

    /**
     * @return string affiliation
     */
    function getAffiliation();

    /**
     * @return string scopedAffiliation
     */
    function getScopedAffiliation();

    /**
     * @param null|string $value
     * @return bool
     */
    function hasAffiliation($value = null);

    /**
     * @param null|string $value
     * @return bool
     */
    function hasScopedAffiliation($value = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    function isMember($scope = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    function isEmployee($scope = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    function isStudent($scope = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    function isStaff($scope = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    function isFaculty($scope = null);

    /**
     * @return string logoutURL
     */
    function getLogoutURL();

    /**
     * @return string
     */
    public function serialize();

    /**
     * @param string $serialized
     */
    public function unserialize($serialized);

    /**
     * @return string
     */
    public function __toString();
}
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
    public function getCommonName();

    /**
     * @return string givenName
     */
    public function getGivenName();

    /**
     * @return string sn
     */
    public function getSurname();

    /**
     * Alias for cn, fallback to uid
     *
     * @return string cn|uid
     */
    public function getDisplayName();

    /**
     * @return string mail (single)
     */
    public function getEmail();

    /**
     * @return string affiliation
     */
    public function getAffiliation();

    /**
     * @return string scopedAffiliation
     */
    public function getScopedAffiliation();

    /**
     * @param null|string $value
     * @return bool
     */
    public function hasAffiliation($value = null);

    /**
     * @param null|string $value
     * @return bool
     */
    public function hasScopedAffiliation($value = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    public function isMember($scope = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    public function isEmployee($scope = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    public function isStudent($scope = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    public function isStaff($scope = null);

    /**
     * @param null|string $scope
     * @return bool
     */
    public function isFaculty($scope = null);

    /**
     * @return string logoutURL
     */
    public function getLogoutURL();

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

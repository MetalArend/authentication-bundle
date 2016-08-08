<?php

namespace Kuleuven\AuthenticationBundle\Model;

use Kuleuven\AuthenticationBundle\Traits\ShibbolethAttributesResolverTrait;

class KuleuvenUser implements KuleuvenUserInterface
{
    use ShibbolethAttributesResolverTrait;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var array
     */
    protected $roles;

    /**
     * @param string $username
     * @param array  $attributes
     * @param array  $roles
     */
    public function __construct($username, array $attributes, $roles = [])
    {
        $this->username = $username;

        $this->setAttributes($attributes);

        if ($this->isStudent()) {
            $roles[] = 'ROLE_STUDENT';
        }
        if ($this->isEmployee()) {
            $roles[] = 'ROLE_EMPLOYEE';
        }
        if ($this->isFaculty()) {
            $roles[] = 'ROLE_FACULTY';
        }
        if ($this->isMember()) {
            $roles[] = 'ROLE_MEMBER';
        }
        if ($this->isStaff()) {
            $roles[] = 'ROLE_STAFF';
        }
        $this->roles = !empty($roles) ? $roles : [];
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @return null
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getSalt()
    {
        return null;
    }

    /**
     *
     */
    public function eraseCredentials()
    {
        // noop
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(
            [
                $this->username,
                $this->attributes,
            ]
        );
    }

    /**
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        $data = array_merge($data, array_fill(0, 5, null));

        list(
            $this->username,
            $this->attributes,
            ) = $data;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getUsername();
    }
}

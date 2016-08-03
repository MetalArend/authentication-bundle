<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Kuleuven\AuthenticationBundle\Traits\ShibbolethAttributesResolverTrait;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class KuleuvenUserToken extends AbstractToken implements TokenInterface
{
    use ShibbolethAttributesResolverTrait;

    public function __construct($user = null, array $attributes = [], array $roles = [])
    {
        $this->setUser($user);
        
        $this->setAttributes($attributes);

        if (empty($roles) && $user instanceof UserInterface) {
            $roles = $user->getRoles();
        }
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
        parent::__construct($roles);
    }

    public function getCredentials()
    {
        return '';
    }

    public function getProviderKey()
    {
        return 'kuleuven_authentication';
    }
}

<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Kuleuven\AuthenticationBundle\Model\KuleuvenUser;
use Kuleuven\AuthenticationBundle\Model\KuleuvenUserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class LdapUserProvider implements UserProviderInterface
{
    /**
     * @var LdapAttributesProvider
     */
    protected $ldapAttributesProvider;

    /**
     * @param LdapAttributesProvider $ldapAttributesProvider
     */
    public function __construct(LdapAttributesProvider $ldapAttributesProvider)
    {
        $this->ldapAttributesProvider = $ldapAttributesProvider;
    }

    /**
     * @inheritdoc
     */
    public function loadUserByUsername($username)
    {
        $attributes = $this->ldapAttributesProvider->getAttributesByUid($username);
        if (empty($attributes)) {
            throw new UsernameNotFoundException(sprintf('Username %s not found', $username));
        }

        return new KuleuvenUser(
            $attributes['uid'],
            $attributes
        );
    }

    /**
     * @inheritdoc
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user))) {
            throw new UnsupportedUserException(sprintf('Class "%s" should implement "%s".', get_class($user), KuleuvenUserInterface::class));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    /**
     * @inheritdoc
     */
    public function supportsClass($class)
    {
        $interfaces = class_implements($class);
        return isset($interfaces[KuleuvenUserInterface::class]);
    }
}

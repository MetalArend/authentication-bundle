<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Kuleuven\AuthenticationBundle\Model\KuleuvenUser;
use Kuleuven\AuthenticationBundle\Model\KuleuvenUserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ShibbolethUserProvider implements UserProviderInterface
{
    /**
     * @var ShibbolethServiceProvider
     */
    protected $shibbolethServiceProvider;

    /**
     * @param ShibbolethServiceProvider $shibbolethServiceProvider
     */
    public function __construct(ShibbolethServiceProvider $shibbolethServiceProvider)
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
    }

    /**
     * @inheritdoc
     */
    public function loadUserByUsername($username)
    {
        if (!$this->shibbolethServiceProvider->isAuthenticated()) {
            throw new UsernameNotFoundException(sprintf('Username %s not found', $username));
        }
        if ($this->shibbolethServiceProvider->getUsername() !== $username) {
            throw new UsernameNotFoundException(sprintf('User %s is not authenticated by Shibboleth.', $username));
        }

        return new KuleuvenUser(
            $this->shibbolethServiceProvider->getUsername(),
            $this->shibbolethServiceProvider->getAttributes()
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

        if ($this->shibbolethServiceProvider->getUsername() !== $user->getUsername()) {
            throw new UnsupportedUserException(sprintf('User "%s" is not authenticated by Shibboleth.', $user->getUsername()));
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

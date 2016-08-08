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
     * @var AttributeDefinitionsProviderInterface
     */
    protected $attributeDefinitionsProvider;

    /**
     * @param ShibbolethServiceProvider             $shibbolethServiceProvider
     * @param AttributeDefinitionsProviderInterface $attributeDefinitionsProvider
     */
    public function __construct(ShibbolethServiceProvider $shibbolethServiceProvider, AttributeDefinitionsProviderInterface $attributeDefinitionsProvider)
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
        $this->attributeDefinitionsProvider = $attributeDefinitionsProvider;
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

        $attributeDefinitions = $this->attributeDefinitionsProvider->getAttributeDefinitions();
        $attributes = $this->shibbolethServiceProvider->getAttributes();
        foreach ($attributes as $name => &$value) {
            if (!isset($attributeDefinitions[$name])) {
                continue;
            }
            $attributeDefinition = $attributeDefinitions[$name];
            $charset = isset($attributeDefinition['charset']) ? $attributeDefinition['charset'] : 'UTF-8';
            if ($charset == 'UTF-8') {
                $value = utf8_decode($value);
            }
            if (isset($attributeDefinition['multivalue']) && $attributeDefinition['multivalue']) {
                $value = explode(';', $value); // $value is an array
            }
        }

        return new KuleuvenUser(
            $username,
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

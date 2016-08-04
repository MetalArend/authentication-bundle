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
     * @var array
     */
    protected $attributeDefinitions;

    /**
     * @param LdapAttributesProvider $ldapAttributesProvider
     * @param array                  $attributeDefinitions
     */
    public function __construct(LdapAttributesProvider $ldapAttributesProvider, array $attributeDefinitions)
    {
        $this->ldapAttributesProvider = $ldapAttributesProvider;
        $this->attributeDefinitions = $attributeDefinitions;
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

        foreach ($attributes as $name => &$value) {
            if (!isset($this->attributeDefinitions[$name])) {
                continue;
            }
            $attributeDefinition = $this->attributeDefinitions[$name];
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

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
        $ldapAttributes = $this->ldapAttributesProvider->getAttributesByFilter(['uid' => $username]);
        if (empty($ldapAttributes)) {
            throw new UsernameNotFoundException(sprintf('Username %s not found', $username));
        }

        $attributes = [];
        foreach ($this->attributeDefinitions as $idOrAlias => $attributeDefinition) {
            $value = null;
            switch (true) {
                case isset($ldapAttributes[$idOrAlias]):
                    $value = $ldapAttributes[$idOrAlias];
                    break;
                case isset($ldapAttributes[strtolower($idOrAlias)]):
                    $value = $ldapAttributes[strtolower($idOrAlias)];
                    break;
                default:
                    continue 2; // switch is considered a looping structure, we have to continue the foreach
            }
            $charset = isset($attributeDefinition['charset']) ? $attributeDefinition['charset'] : 'UTF-8';
            if ($charset == 'UTF-8') {
                $value = utf8_decode($value);
            }
            if (isset($attributeDefinition['multivalue']) && $attributeDefinition['multivalue']) {
                $value = explode(';', $value); // $value is an array
            }
            $id = $attributeDefinition['id'];
            $aliases = $attributeDefinition['aliases'];
            $attributes[$id] = $value;
            foreach ($aliases as $alias) {
                $attributes[$alias] = $value;
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

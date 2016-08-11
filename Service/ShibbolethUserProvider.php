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
     * @var AttributesByUsernameProviderInterface
     */
    protected $attributesProvider;

    /**
     * @var AttributeDefinitionsProviderInterface
     */
    protected $attributeDefinitionsProvider;

    /**
     * @param AttributesByUsernameProviderInterface $attributesProvider
     * @param AttributeDefinitionsProviderInterface $attributeDefinitionsProvider
     */
    public function __construct(AttributesByUsernameProviderInterface $attributesProvider, AttributeDefinitionsProviderInterface $attributeDefinitionsProvider)
    {
        $this->attributesProvider = $attributesProvider;
        $this->attributeDefinitionsProvider = $attributeDefinitionsProvider;
    }

    /**
     * @inheritdoc
     */
    public function loadUserByUsername($username)
    {
        $providerAttributes = $this->attributesProvider->getAttributesByUsername($username);
        if (empty($providerAttributes)) {
            throw new UsernameNotFoundException(sprintf('User "%s" not found.', $username));
        }

        $attributeDefinitions = $this->attributeDefinitionsProvider->getAttributeDefinitions();
        $attributes = [];
        foreach ($attributeDefinitions as $idOrAlias => $attributeDefinition) {
            $value = null;
            switch (true) {
                case isset($providerAttributes[$idOrAlias]):
                    $value = $providerAttributes[$idOrAlias];
                    break;
                case isset($providerAttributes[strtolower($idOrAlias)]):
                    $value = $providerAttributes[strtolower($idOrAlias)];
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

        try {
            $user = $this->loadUserByUsername($user->getUsername());
        } catch (UsernameNotFoundException $exception) {
            throw new UnsupportedUserException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $user;
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

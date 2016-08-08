<?php

namespace Kuleuven\AuthenticationBundle\Collector;

use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Kuleuven\AuthenticationBundle\Security\KuleuvenUserToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationDataCollector extends DataCollector implements DataCollectorInterface
{
    /**
     * @var null|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var null|RoleHierarchyInterface
     */
    protected $roleHierarchy;

    /**
     * @var ShibbolethServiceProvider
     */
    protected $shibbolethServiceProvider;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param ShibbolethServiceProvider   $shibbolethServiceProvider
     * @param TokenStorageInterface|null  $tokenStorage
     * @param RoleHierarchyInterface|null $roleHierarchy
     * @param KernelInterface             $kernel
     */
    public function __construct(ShibbolethServiceProvider $shibbolethServiceProvider, TokenStorageInterface $tokenStorage = null, RoleHierarchyInterface $roleHierarchy = null, KernelInterface $kernel)
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
        $this->tokenStorage = $tokenStorage;
        $this->roleHierarchy = $roleHierarchy;
        $this->kernel = $kernel;
        $this->data = [];
    }

    /**
     * @return bool
     */
    protected function isTokenStorageEnabled()
    {
        return null !== $this->tokenStorage;
    }

    /**
     * @return null|KuleuvenUserToken
     */
    protected function findToken()
    {
        if (!$this->isTokenStorageEnabled()) {
            return null;
        }
        return $this->tokenStorage->getToken();
    }

    /**
     * @return null|KuleuvenUserToken
     */
    protected function findSourceToken()
    {
        $token = $this->findToken();
        if (null === $token) {
            return null;
        }
        $roles = $token->getRoles();
        foreach ($roles as $role) {
            if ($role instanceof SwitchUserRole) {
                return $role->getSource();
            }
        }
        return null;
    }

    /**
     * @param $roles
     * @return array
     */
    protected function findInheritedRoles($roles)
    {
        $inheritedRoles = [];
        if (null !== $this->roleHierarchy) {
            $allRoles = $this->roleHierarchy->getReachableRoles($roles);
            foreach ($allRoles as $role) {
                if (!in_array($role, $roles, true)) {
                    $inheritedRoles[] = $role;
                }
            }
        }
        return $inheritedRoles;
    }

    /**
     * @inheritdoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $image = base64_encode(file_get_contents($this->kernel->locateResource('@KuleuvenAuthenticationBundle/Resources/images/shibboleth.png')));
        $this->data = array_replace($this->data, [
            'image'      => $image,
            'shibboleth' => [
                'username'               => $this->shibbolethServiceProvider->getUsername(),
                'handlerPath'            => $this->shibbolethServiceProvider->getHandlerPath(),
                'statusPath'             => $this->shibbolethServiceProvider->getStatusPath(),
                'sessionLoginPath'       => $this->shibbolethServiceProvider->getSessionLoginPath(),
                'sessionLogoutPath'      => $this->shibbolethServiceProvider->getSessionLogoutPath(),
                'sessionOverviewPath'    => $this->shibbolethServiceProvider->getSessionOverviewPath(),
                'handlerUrl'             => $this->shibbolethServiceProvider->getHandlerUrl(),
                'statusUrl'              => $this->shibbolethServiceProvider->getStatusUrl(),
                'loginUrl'               => $this->shibbolethServiceProvider->getLoginUrl(),
                'logoutUrl'              => $this->shibbolethServiceProvider->getLogoutUrl(),
                'overviewUrl'            => $this->shibbolethServiceProvider->getOverviewUrl(),
                'reachable'              => $this->shibbolethServiceProvider->isReachable(),
                'securedHandler'         => $this->shibbolethServiceProvider->isSecuredHandler(),
                'usernameAttribute'      => $this->shibbolethServiceProvider->getUsernameAttribute(),
                'authenticatedAttribute' => $this->shibbolethServiceProvider->getAuthenticatedAttribute(),
                'logoutUrlAttribute'     => $this->shibbolethServiceProvider->getLogoutUrlAttribute(),
                'defaultCharset'         => $this->shibbolethServiceProvider->getDefaultCharset(),
                'attributes'             => $this->shibbolethServiceProvider->getAttributes(),
            ],
        ]);

        if (null === $token = $this->findToken()) {
            $this->data = array_replace($this->data, [
                'enabled'                 => $this->isTokenStorageEnabled(),
                'authenticated'           => false,
                'token_class'             => null,
                'user'                    => '',
                'display_name'            => '',
                'affiliation'             => null,
                'attributes'              => [],
                'roles'                   => [],
                'inherited_roles'         => [],
                'supports_role_hierarchy' => null !== $this->roleHierarchy,
            ]);
        } else {
            $this->data = array_replace($this->data, [
                'enabled'                 => $this->isTokenStorageEnabled(),
                'authenticated'           => $token->isAuthenticated(),
                'token_class'             => get_class($token),
                'user'                    => $token->getUsername(),
                'display_name'            => $token->getUser()->getDisplayName(),
                'affiliation'             => $token->getUser()->getAffiliation(),
                'attributes'              => $token->getUser()->getAttributes(),
                'roles'                   => array_map(function (RoleInterface $role) {
                    return $role->getRole();
                }, $token->getRoles()),
                'inherited_roles'         => array_map(function (RoleInterface $role) {
                    return $role->getRole();
                }, $this->findInheritedRoles($token->getRoles())),
                'supports_role_hierarchy' => null !== $this->roleHierarchy,
            ]);
        }

        if (null === $source = $this->findSourceToken()) {
            $this->data = array_replace($this->data, [
                'source_authenticated'   => false,
                'source_token_class'     => null,
                'source_user'            => '',
                'source_display_name'    => '',
                'source_affiliation'     => null,
                'source_attributes'      => [],
                'source_roles'           => [],
                'source_inherited_roles' => [],
            ]);
        } else {
            $this->data = array_replace($this->data, [
                'source_authenticated'   => $source->isAuthenticated(),
                'source_token_class'     => get_class($source),
                'source_user'            => $source->getUsername(),
                'source_affiliation'     => $source->getUser()->getAffiliation(),
                'source_display_name'    => $source->getUser()->getDisplayName(),
                'source_attributes'      => $source->getUser()->getAttributes(),
                'source_roles'           => array_map(function (RoleInterface $role) {
                    return $role->getRole();
                }, $source->getRoles()),
                'source_inherited_roles' => array_map(function (RoleInterface $role) {
                    return $role->getRole();
                }, $this->findInheritedRoles($source->getRoles())),
            ]);
        }
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return $this->data['image'];
    }

    /**
     * @return array
     */
    public function getShibboleth()
    {
        return $this->data['shibboleth'];
    }

    /**
     * Checks if the data contains information about inherited roles. Still the inherited
     * roles can be an empty array.
     *
     * @return bool true if the profile was contains inherited role information.
     */
    public function supportsRoleHierarchy()
    {
        return $this->data['supports_role_hierarchy'];
    }

    /**
     * Checks if security is enabled.
     *
     * @return bool true if security is enabled, false otherwise
     */
    public function isEnabled()
    {
        return $this->data['enabled'];
    }

    /**
     * Gets the user.
     *
     * @return string The user
     */
    public function getUser()
    {
        return $this->data['user'];
    }

    /**
     * Gets the display name.
     *
     * @return string The display name
     */
    public function getDisplayName()
    {
        return $this->data['display_name'];
    }

    /**
     * Gets the unscoped affiliation.
     *
     * @return string The unscoped affiliation
     */
    public function getAffiliation()
    {
        return $this->data['affiliation'];
    }

    /**
     * Gets all the attributes.
     *
     * @return string The attributes
     */
    public function getAttributes()
    {
        return $this->data['attributes'];
    }

    /**
     * Gets the roles of the user.
     *
     * @return array The roles
     */
    public function getRoles()
    {
        return $this->data['roles'];
    }

    /**
     * Gets the inherited roles of the user.
     *
     * @return array The inherited roles
     */
    public function getInheritedRoles()
    {
        return $this->data['inherited_roles'];
    }

    /**
     * Checks if the user is authenticated or not.
     *
     * @return bool true if the user is authenticated, false otherwise
     */
    public function isAuthenticated()
    {
        return $this->data['authenticated'];
    }

    /**
     * Get the class name of the security token.
     *
     * @return string The token
     */
    public function getTokenClass()
    {
        return $this->data['token_class'];
    }

    /**
     * Gets the source user.
     *
     * @return string The source user
     */
    public function getSourceUser()
    {
        return $this->data['source_user'];
    }

    /**
     * Gets the display name.
     *
     * @return string The display name
     */
    public function getSourceDisplayName()
    {
        return $this->data['source_display_name'];
    }

    /**
     * Gets the unscoped affiliation.
     *
     * @return string The unscoped affiliation
     */
    public function getSourceAffiliation()
    {
        return $this->data['source_affiliation'];
    }

    /**
     * Gets all the attributes.
     *
     * @return string The attributes
     */
    public function getSourceAttributes()
    {
        return $this->data['source_attributes'];
    }

    /**
     * Gets the roles of the source user.
     *
     * @return array The source roles
     */
    public function getSourceRoles()
    {
        return $this->data['source_roles'];
    }

    /**
     * Gets the inherited roles of the source user.
     *
     * @return array The source inherited roles
     */
    public function getSourceInheritedRoles()
    {
        return $this->data['source_inherited_roles'];
    }

    /**
     * Checks if the source user is authenticated or not.
     *
     * @return bool true if the source user is authenticated, false otherwise
     */
    public function isSourceAuthenticated()
    {
        return $this->data['source_authenticated'];
    }

    /**
     * Get the class name of the source security token.
     *
     * @return string The source token
     */
    public function getSourceTokenClass()
    {
        return $this->data['source_token_class'];
    }

    /**
     * @return null|UserInterface
     */
    public function getSessionUser()
    {
        return $this->data['session_user'];
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'kuleuven_authentication';
    }
}
<?php

namespace Kuleuven\AuthenticationBundle\Collector;

use Kuleuven\AuthenticationBundle\Model\KuleuvenUser;
use Kuleuven\AuthenticationBundle\Security\ShibbolethAuthenticationListener;
use Kuleuven\AuthenticationBundle\Service\FirewallHelper;
use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Kuleuven\AuthenticationBundle\Security\KuleuvenUserToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationDataCollector extends DataCollector implements DataCollectorInterface
{
    /**
     * @var ShibbolethServiceProvider
     */
    protected $shibbolethServiceProvider;

    /**
     * @var FirewallHelper
     */
    protected $firewallHelper;

    /**
     * @var null|TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @param ShibbolethServiceProvider  $shibbolethServiceProvider
     * @param FirewallHelper             $firewallHelper
     * @param TokenStorageInterface|null $tokenStorage
     * @param KernelInterface            $kernel
     */
    public function __construct(ShibbolethServiceProvider $shibbolethServiceProvider, FirewallHelper $firewallHelper, KernelInterface $kernel, TokenStorageInterface $tokenStorage = null)
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
        $this->firewallHelper = $firewallHelper;
        $this->tokenStorage = $tokenStorage;
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
     * @return TokenInterface
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
     * @inheritdoc
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $image = base64_encode(file_get_contents($this->kernel->locateResource('@KuleuvenAuthenticationBundle/Resources/images/shibboleth.png')));
        $this->data = array_replace($this->data, [
            'image'      => $image,
            'shibboleth' => [
                'username'                   => $this->shibbolethServiceProvider->getUsername(),
                'authenticated'              => $this->shibbolethServiceProvider->isAuthenticated(),
                'handlerPath'                => $this->shibbolethServiceProvider->getHandlerPath(),
                'statusPath'                 => $this->shibbolethServiceProvider->getStatusPath(),
                'sessionLoginPath'           => $this->shibbolethServiceProvider->getSessionLoginPath(),
                'sessionLogoutPath'          => $this->shibbolethServiceProvider->getSessionLogoutPath(),
                'sessionOverviewPath'        => $this->shibbolethServiceProvider->getSessionOverviewPath(),
                'handlerUrl'                 => $this->shibbolethServiceProvider->getHandlerUrl(),
                'statusUrl'                  => $this->shibbolethServiceProvider->getStatusUrl(),
                'loginUrl'                   => $this->shibbolethServiceProvider->getLoginUrl(),
                'logoutUrl'                  => $this->shibbolethServiceProvider->getLogoutUrl(),
                'overviewUrl'                => $this->shibbolethServiceProvider->getOverviewUrl(),
                'reachable'                  => $this->shibbolethServiceProvider->isReachable(),
                'securedHandler'             => $this->shibbolethServiceProvider->isSecuredHandler(),
                'usernameAttribute'          => $this->shibbolethServiceProvider->getUsernameAttribute(),
                'authenticatedAttribute'     => $this->shibbolethServiceProvider->getAuthenticatedAttribute(),
                'logoutUrlAttribute'         => $this->shibbolethServiceProvider->getLogoutUrlAttribute(),
                'authenticationRequirements' => $this->shibbolethServiceProvider->getAuthenticationRequirements(),
                'defaultCharset'             => $this->shibbolethServiceProvider->getDefaultCharset(),
                'attributes'                 => $this->shibbolethServiceProvider->getAttributes(),
            ],
        ]);

        $token = $this->findToken();
        $user = (!empty($token) ? $token->getUser() : null);
        $sourceToken = $this->findSourceToken();
        $source = (!empty($sourceToken) ? $sourceToken->getUser() : null);

        $shibbolethUser = (empty($source) ? $user : $source);
        $impersonatedUser = (empty($source) ? null : $user);

        $this->data = array_replace($this->data, [
            'enabled'                   => $this->firewallHelper->isProtectedBy(ShibbolethAuthenticationListener::class),
            'authenticated'             => $this->shibbolethServiceProvider->isAuthenticated(),
            'user'                      => ($shibbolethUser instanceof UserInterface ? $shibbolethUser->getUsername() : $shibbolethUser),
            'display_name'              => ($shibbolethUser instanceof KuleuvenUser ? $shibbolethUser->getDisplayName() : ''),
            'affiliation'               => ($shibbolethUser instanceof KuleuvenUser ? $shibbolethUser->getAffiliation() : ''),
            'attributes'                => ($shibbolethUser instanceof KuleuvenUser ? $shibbolethUser->getAttributes() : []),
            'token_class'               => (!empty($token) ? get_class($token) : ''),
            'impersonated_user'         => ($impersonatedUser instanceof UserInterface ? $impersonatedUser->getUsername() : $impersonatedUser),
            'impersonated_display_name' => ($impersonatedUser instanceof KuleuvenUser ? $impersonatedUser->getDisplayName() : ''),
            'impersonated_affiliation'  => ($impersonatedUser instanceof KuleuvenUser ? $impersonatedUser->getAffiliation() : ''),
            'impersonated_attributes'   => ($impersonatedUser instanceof KuleuvenUser ? $impersonatedUser->getAttributes() : []),
            'impersonated_token_class'  => (!empty($sourceToken) ? get_class($sourceToken) : ''),
        ]);
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
     * Checks if security is enabled.
     *
     * @return bool true if security is enabled, false otherwise
     */
    public function isEnabled()
    {
        return $this->data['enabled'];
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
    public function getImpersonatedUser()
    {
        return $this->data['impersonated_user'];
    }

    /**
     * Gets the display name.
     *
     * @return string The display name
     */
    public function getImpersonatedDisplayName()
    {
        return $this->data['impersonated_display_name'];
    }

    /**
     * Gets the unscoped affiliation.
     *
     * @return string The unscoped affiliation
     */
    public function getImpersonatedAffiliation()
    {
        return $this->data['impersonated_affiliation'];
    }

    /**
     * Gets all the attributes.
     *
     * @return string The attributes
     */
    public function getImpersonatedAttributes()
    {
        return $this->data['impersonated_attributes'];
    }

    /**
     * Get the class name of the source security token.
     *
     * @return string The source token
     */
    public function getImpersonatedTokenClass()
    {
        return $this->data['impersonated_token_class'];
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'kuleuven_authentication';
    }
}

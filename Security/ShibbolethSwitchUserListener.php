<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Kuleuven\AuthenticationBundle\Model\KuleuvenUserInterface;
use Kuleuven\AuthenticationBundle\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ShibbolethSwitchUserListener implements ListenerInterface, LoggerAwareInterface
{
    use LoggerTrait;

    protected $tokenStorage;
    protected $provider;
    protected $userChecker;
    protected $providerKey;
    protected $accessDecisionManager;
    protected $usernameParameter;
    protected $role;
    protected $dispatcher;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserProviderInterface $provider,
        UserCheckerInterface $userChecker,
        $providerKey,
        AccessDecisionManagerInterface $accessDecisionManager,
        LoggerInterface $logger = null,
        $usernameParameter = '_switch_user',
        $role = 'ROLE_ALLOWED_TO_SWITCH',
        EventDispatcherInterface $dispatcher = null
    )
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->tokenStorage = $tokenStorage;
        $this->provider = $provider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
        $this->accessDecisionManager = $accessDecisionManager;
        // use LoggerTrait
        if (!empty($logger)) {
            $this->setLogger($logger);
        }
        $this->usernameParameter = $usernameParameter;
        $this->role = $role;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handles the switch to another user.
     *
     * @param GetResponseEvent $event A GetResponseEvent instance
     * @throws AccessDeniedException
     * @throws AuthenticationException
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $username = $event->getRequest()->get($this->usernameParameter);
        if (empty($username)) {
            // This is not a switch request
            return;
        }

        $token = $this->tokenStorage->getToken();
        if (!empty($token) && $token instanceof KuleuvenUserToken) {
            if ('_exit' === $username) {
                $originalToken = $this->getOriginalToken($token);
                if (false === $originalToken) {
                    $this->log('Exit attempt ignored, no original token found');
                } else {
                    $authenticationToken = $this->attemptSwitchUser($request, $originalToken->getUsername());
                    $this->log(sprintf('Switch to new authentication token: %s', $authenticationToken));
                    $this->tokenStorage->setToken($authenticationToken);
                }
            } else {
                try {
                    $authenticationToken = $this->attemptSwitchUser($request, $request->get($this->usernameParameter));
                    $this->log(sprintf('Switch to new authentication token: %s', $authenticationToken));
                    $this->tokenStorage->setToken($authenticationToken);
                } catch (AuthenticationException $e) {
                    $this->log(sprintf('Switch User failed: "%s"', $e->getMessage()));
                    throw $e;
                }
            }
        }

        $this->log(sprintf('Redirect to original url: %s', $request->getUri()));
        $request->query->remove($this->usernameParameter);
        $request->server->set('QUERY_STRING', http_build_query($request->query->all()));
        $response = new RedirectResponse($request->getUri(), 302);
        $event->setResponse($response);
    }

    /**
     * Attempts to switch to another user.
     *
     * @param Request $request A Request instance
     * @param string  $username
     * @return null|TokenInterface The new TokenInterface if successfully switched, null otherwise
     * @throws AccessDeniedException
     */
    private function attemptSwitchUser(Request $request, $username)
    {
        /** @var KuleuvenUserToken $token */
        $token = $this->tokenStorage->getToken();
        $token->setUser($this->provider->refreshUser($token->getUser()));

        if ($token->getUsername() === $username) {
            $this->log(sprintf('Token already for username "%s", keep token: %s', $username, $token));
            return $token;
        }

        $originalToken = $this->getOriginalToken($token);
        if (false !== $originalToken) {
            $this->log(sprintf('Original token found: %s', $originalToken));
            // User is impersonating someone, they are trying to switch directly to another user, make sure original user has access.
            if (false === $this->accessDecisionManager->decide($originalToken, [$this->role])) {
                $this->log(sprintf('Original token has no right to impersonate "%s", access denied: %s', $username, $originalToken));
                throw new AccessDeniedException(sprintf('Original token has no right to impersonate "%s", access denied: %s', $username, $originalToken));
            }
            if ($originalToken->getUsername() === $username) {
                $this->log(sprintf('Original token is already for "%s", switching to original token: %s', $username, $originalToken));
                if (null !== $this->dispatcher) {
                    $switchEvent = new ShibbolethSwitchUserEvent($request, $originalToken->getUser(), $originalToken);
                    $this->dispatcher->dispatch(SecurityEvents::SWITCH_USER, $switchEvent);
                }
                return $originalToken;
            }
        } elseif (false === $this->accessDecisionManager->decide($token, [$this->role])) {
            $this->log(sprintf('Token has no right to impersonate "%s", access denied: %s', $username, $originalToken));
            throw new AccessDeniedException(sprintf('Token has no right to impersonate "%s", access denied: %s', $username, $originalToken));
        }

        $this->log(sprintf('Attempting to impersonate "%s"', $username));

        /** @var KuleuvenUserInterface $user */
        $user = $this->provider->loadUserByUsername($username);
        $this->userChecker->checkPostAuth($user);

        $attributes = $user->getAttributes();
        $roles = $user->getRoles();

        // If there is an original token, only let them switch back to that user.
        if ($originalToken) {
            $roles[] = new SwitchUserRole('ROLE_PREVIOUS_ADMIN', $originalToken);
        } else {
            $roles[] = new SwitchUserRole('ROLE_PREVIOUS_ADMIN', $token);
        }

        $token = new KuleuvenUserToken($user, $attributes, $this->providerKey, $roles);
        $token->setAuthenticated(true);

        if (null !== $this->dispatcher) {
            $switchEvent = new ShibbolethSwitchUserEvent($request, $token->getUser(), $token);
            $this->dispatcher->dispatch(SecurityEvents::SWITCH_USER, $switchEvent);
        }

        return $token;
    }

    /**
     * Gets the original Token from a switched one.
     *
     * @param KuleuvenUserToken $token A switched TokenInterface instance
     * @return TokenInterface|false The original TokenInterface instance, false if the current TokenInterface is not switched
     */
    private function getOriginalToken(KuleuvenUserToken $token)
    {
        foreach ($token->getRoles() as $role) {
            if ($role instanceof SwitchUserRole) {
                return $role->getSource();
            }
        }

        return false;
    }
}

<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Kuleuven\AuthenticationBundle\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\SwitchUserRole;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Http\SecurityEvents;

class ShibbolethAuthenticationListener implements ListenerInterface, LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * @var ShibbolethServiceProvider
     */
    protected $shibbolethServiceProvider;

    /**
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    /**
     * @var AuthenticationEntryPointInterface
     */
    protected $authenticationEntryPoint;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $defaultRoles;

    public function __construct(
        ShibbolethServiceProvider $shibbolethServiceProvider,
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager,
        AuthenticationEntryPointInterface $authenticationEntryPoint = null,
        EventDispatcherInterface $eventDispatcher = null,
        array $defaultRoles = []
    )
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
        $this->authenticationEntryPoint = $authenticationEntryPoint;
        $this->eventDispatcher = $eventDispatcher;
        $this->defaultRoles = $defaultRoles;
    }

    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $this->log(
            basename(__FILE__) .
            ' - ' .
            sprintf(
                'Shibboleth attributes found: %s',
                json_encode($this->shibbolethServiceProvider->getAttributes())
            )
        );

        if (!$this->shibbolethServiceProvider->isAuthenticated()) {
            $this->log(basename(__FILE__) . ' - ' . 'Authentication key not found');
            return;
        }

        $username = $this->shibbolethServiceProvider->getUsername();
        $this->log(basename(__FILE__) . ' - ' . sprintf('Username found: %s', $username));
        $token = $this->tokenStorage->getToken();

        if (!empty($token)) {
            if ($token instanceof KuleuvenUserToken && $token->isAuthenticated()) {
                $this->log(basename(__FILE__) . ' - ' . sprintf('Token found: %s', $token));
                if ($token->getUsername() === $username && count($token->getRoles()) === count($token->getUser()->getRoles())) {
                    $this->log(basename(__FILE__) . ' - ' . sprintf('Token authenticated for username "%s": %s', $username, $token));
                    return;
                }
                $roles = $token->getRoles();
                foreach ($roles as $role) {
                    if ($role instanceof SwitchUserRole) {
                        if ($role->getSource()->getUser()->getUsername() === $username) {
                            $this->log(basename(__FILE__) . ' - ' . sprintf('Token authenticated for username "%s", impersonating "%s": %s', $username, $token->getUsername(), $token));
                            return;
                        }
                        break;
                    }
                }
            }
        }

        try {
            $token = new KuleuvenUserToken(
                $this->shibbolethServiceProvider->getUsername(),
                $this->shibbolethServiceProvider->getAttributes(),
                $this->defaultRoles
            );
            $this->log(basename(__FILE__) . ' - ' . sprintf('Token created for username "%s": %s', $username, $token));

            $authenticationToken = $this->authenticationManager->authenticate($token);
            if ($authenticationToken instanceof TokenInterface) {
                $this->log(basename(__FILE__) . ' - ' . sprintf('Set authentication token: %s', $authenticationToken));
                $this->tokenStorage->setToken($authenticationToken);
                if (null !== $this->eventDispatcher) {
                    $loginEvent = new InteractiveLoginEvent($request, $authenticationToken);
                    $this->log(basename(__FILE__) . ' - ' . 'Dispatch login event');
                    $this->eventDispatcher->dispatch(SecurityEvents::INTERACTIVE_LOGIN, $loginEvent);
                }
            } elseif ($authenticationToken instanceof Response) {
                $this->log(basename(__FILE__) . ' - ' . 'Using authentication token as response...');
                $event->setResponse($authenticationToken);
            }
        } catch (AuthenticationException $failed) {
            $this->log(basename(__FILE__) . ' - ' . sprintf('Authentication request failed for username "%s": %s', $username, $failed->getMessage()));

            $token = $this->tokenStorage->getToken();
            if ($token instanceof KuleuvenUserToken) {
                $this->log(basename(__FILE__) . ' - ' . 'Remove token');
                $this->tokenStorage->setToken(null);
            }

            try {
                $event->setResponse($this->authenticationEntryPoint->start($request, $failed));
            } catch (AuthenticationException $failed) {
                $this->log(basename(__FILE__) . ' - ' . 'Entry point failed, sending forbidden response...');
                $response = (new Response());
                $response->setStatusCode(Response::HTTP_FORBIDDEN);
                $event->setResponse($response);
            }
        }
    }
}

<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Kuleuven\AuthenticationBundle\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class ShibbolethSwitchUserPersistenceSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerTrait;

    protected $session;
    protected $tokenStorage;
    protected $shibbolethServiceProvider;
    protected $authenticationManager;
    protected $userProvider;
    protected $userChecker;
    protected $accessDecisionManager;
    protected $usernameParameter;
    protected $role;
    protected $sessionKey;
    protected $eventDispatcher;

    public function __construct(
        SessionInterface $session,
        TokenStorageInterface $tokenStorage,
        ShibbolethServiceProvider $shibbolethServiceProvider,
        AuthenticationManagerInterface $authenticationManager,
        $sessionKey = 'authentication'
    )
    {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
        $this->authenticationManager = $authenticationManager;
        $this->sessionKey = $sessionKey;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST       => [['onKernelRequest', 255]],
            SecurityEvents::SWITCH_USER => [['onUserSwitch', 255]],
        ];
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $token = $this->session->get($this->sessionKey);
        if (empty($token) || !$this->supportsToken($token)) {
            return null;
        }
        $this->log(basename(__FILE__) . ' - ' . sprintf('Persisted token found: %s', $token));

        $token = $this->authenticationManager->authenticate($token);
        $this->tokenStorage->setToken($token);

        return $token;
    }

    public function onUserSwitch(SwitchUserEvent $event)
    {
        if ($event instanceof ShibbolethSwitchUserEvent) {
            if ($event->getTargetUser()->getUsername() !== $this->shibbolethServiceProvider->getUsername()) {
                $this->session->set($this->sessionKey, $event->getToken());
                $this->log(basename(__FILE__) . ' - ' . sprintf('Token persisted for username "%s": %s', $event->getTargetUser()->getUsername(), $event->getToken()));
            } else {
                $this->session->remove($this->sessionKey);
                $this->log(basename(__FILE__) . ' - ' . sprintf('Persisted token cleared: %s', $event->getToken()));
            }
        }
    }

    public function supportsToken(TokenInterface $token)
    {
        return
            $token instanceof KuleuvenUserToken
            && $token->getUsername() !== $this->shibbolethServiceProvider->getUsername();
    }
}
<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Kuleuven\AuthenticationBundle\Traits\LoggerTrait;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
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
        $sessionKey
    )
    {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
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
        $token = $this->tokenStorage->getToken();
        if (!empty($token)) {
            return null;
        }

        $persistedToken = $this->session->get($this->sessionKey);
        if (empty($persistedToken) || !$this->supportsToken($persistedToken)) {
            return null;
        }

        $this->log(sprintf('Token found in session: %s', $persistedToken));

        $this->tokenStorage->setToken($persistedToken);
        $this->log(sprintf('Token written to storage: %s', $persistedToken));
    }

    public function onUserSwitch(SwitchUserEvent $event)
    {
        if ($event instanceof ShibbolethSwitchUserEvent) {
            if ($event->getTargetUser()->getUsername() !== $this->shibbolethServiceProvider->getUsername()) {
                $this->session->set($this->sessionKey, $event->getToken());
                $this->log(sprintf('Token persisted in session for username "%s": %s', $event->getTargetUser()->getUsername(), $event->getToken()));
            } else {
                $this->session->remove($this->sessionKey);
                $this->log(sprintf('Token removed from session: %s', $event->getToken()));
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

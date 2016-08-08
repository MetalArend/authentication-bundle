<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Kuleuven\AuthenticationBundle\Traits\LoggerTrait;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ShibbolethAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    use LoggerTrait;

    private $shibbolethServiceProvider;

    public function __construct(ShibbolethServiceProvider $shibbolethServiceProvider)
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        if (!$this->shibbolethServiceProvider->isAuthenticated()) {
            $this->log('Shibboleth has not authenticated your request.');
            throw new AuthenticationException('Shibboleth has not authenticated your request.');
        }
        $url = $this->shibbolethServiceProvider->getLoginUrl($request->getUri());
        if (!$this->shibbolethServiceProvider->isReachable($url)) {
            $this->log(sprintf('Shibboleth login is not available at "%s".', $url));
            throw new AuthenticationException('Shibboleth login is not available.');
        }
        $this->log(sprintf('Redirecting to login at "%s"...', $url));
        return new RedirectResponse($url);
    }
}

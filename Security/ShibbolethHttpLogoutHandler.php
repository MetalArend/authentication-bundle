<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;
use Kuleuven\AuthenticationBundle\Traits\LoggerTrait;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ShibbolethHttpLogoutHandler implements LogoutHandlerInterface, LogoutSuccessHandlerInterface
{
    use LoggerTrait;

    /**
     * @var ShibbolethServiceProvider
     */
    protected $shibbolethServiceProvider;

    /**
     * @var string
     */
    protected $target;

    /**
     * @param ShibbolethServiceProvider $shibbolethServiceProvider
     * @param null|string               $target
     */
    public function __construct(ShibbolethServiceProvider $shibbolethServiceProvider, $target = null)
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
        $this->target = $target;
    }

    /**
     * @inheritdoc
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        if ($token instanceof KuleuvenUserToken) {
            $request->getSession()->invalidate();
        }
    }

    /**
     * @inheritdoc
     */
    public function onLogoutSuccess(Request $request)
    {
        $target = $this->target;
        if (empty($target)) {
            if ($request->query->has('target')) {
                $target = $request->query->get('target');
            } else {
                $target = $request->headers->get('referer', '/');
            }
        }
        $url = $this->shibbolethServiceProvider->getLogoutUrl($target);
        $this->log(sprintf('Redirecting after logout to "%s"...', $url));
        return new RedirectResponse($url);
    }
}

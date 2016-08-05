<?php

namespace Kuleuven\AuthenticationBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ShibbolethServiceProvider implements AttributesProviderInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var bool
     */
    protected $securedHandler;

    /**
     * @var string
     */
    protected $handlerPath;

    /**
     * @var string
     */
    protected $statusPath;

    /**
     * @var string
     */
    protected $sessionLoginPath;

    /**
     * @var string
     */
    protected $sessionLogoutPath;

    /**
     * @var string
     */
    protected $sessionOverviewPath;

    /**
     * @var string
     */
    protected $usernameAttribute;

    /**
     * @var string
     */
    protected $authenticatedAttribute;

    /**
     * @var string
     */
    protected $logoutUrlAttribute;

    /**
     * @var string
     */
    protected $defaultCharset;

    /**
     * @var array
     */
    protected $attributeDefinitions;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param RequestStack                 $requestStack
     * @param bool                         $securedHandler
     * @param string                       $handlerPath
     * @param string                       $statusPath
     * @param string                       $sessionLoginPath
     * @param string                       $sessionLogoutPath
     * @param string                       $sessionOverviewPath
     * @param string                       $usernameAttribute
     * @param string                       $authenticatedAttribute
     * @param string                       $logoutUrlAttribute
     * @param string                       $defaultCharset
     * @param                              $attributeDefinitions
     */
    public function __construct(
        RequestStack $requestStack,
        $securedHandler,
        $handlerPath,
        $statusPath,
        $sessionLoginPath,
        $sessionLogoutPath,
        $sessionOverviewPath,
        $usernameAttribute,
        $authenticatedAttribute,
        $logoutUrlAttribute,
        $defaultCharset,
        $attributeDefinitions
    )
    {
        $this->requestStack = $requestStack;
        $this->securedHandler = $securedHandler;
        $this->handlerPath = $handlerPath;
        $this->statusPath = $statusPath;
        $this->sessionLoginPath = $sessionLoginPath;
        $this->sessionLogoutPath = $sessionLogoutPath;
        $this->sessionOverviewPath = $sessionOverviewPath;
        $this->usernameAttribute = $usernameAttribute;
        $this->authenticatedAttribute = $authenticatedAttribute;
        $this->logoutUrlAttribute = $logoutUrlAttribute;
        $this->defaultCharset = $defaultCharset;
        $this->attributeDefinitions = $attributeDefinitions;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSecuredHandler()
    {
        return $this->securedHandler;
    }

    /**
     * @return string
     */
    public function getHandlerPath()
    {
        return $this->handlerPath;
    }

    /**
     * @return string
     */
    public function getSessionLoginPath()
    {
        return $this->sessionLoginPath;
    }

    /**
     * @return string
     */
    public function getSessionLogoutPath()
    {
        return $this->sessionLogoutPath;
    }

    /**
     * @return string
     */
    public function getSessionOverviewPath()
    {
        return $this->sessionOverviewPath;
    }

    /**
     * @return string
     */
    public function getStatusPath()
    {
        return $this->statusPath;
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        if (empty($this->request)) {
            $this->request = $this->requestStack->getCurrentRequest();
        }

        return $this->request;
    }

    /**
     * @param null $fallback
     * @return array
     */
    public function getAttributes($fallback = null)
    {
        $attributes = [];
        foreach ($this->attributeDefinitions as $idOrAlias => $attributeDefinition) {
            $attributes[$idOrAlias] = $this->getAttribute($idOrAlias, $fallback);
        }
        return $attributes;
    }

    /**
     * @param string $name
     * @param null   $fallback
     * @return null|string
     */
    public function getAttribute($name, $fallback = null)
    {
        $request = $this->requestStack->getCurrentRequest();
        return $request->server->get($name, $fallback);
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return null !== $this->getAttribute($this->authenticatedAttribute, null);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->getAttribute($this->usernameAttribute);
    }

    /**
     * Returns shibboleth session URL
     *
     * @return string
     */
    public function getHandlerUrl()
    {
        $request = $this->getRequest();
        return (($this->isSecuredHandler()) ? 'https://' : 'http://') . $request->getHost() . $this->getHandlerPath();
    }

    /**
     * Returns URL to initiate login session. After successful login, the user will be redirected
     * to the optional target page. The target can be an absolute or relative URL.
     *
     * @param string|null $target URL to redirect to after successful login. Defaults to the current request URL.
     * @return string The absolute URL to initiate a session
     */
    public function getLoginUrl($target = null)
    {
        $request = $this->getRequest();
        if (empty($target)) {
            $target = $request->getUri();
        }
        return $this->getHandlerUrl() . $this->getSessionLoginPath() . '?target=' . urlencode($target);
    }

    /**
     * Returns URL to invalidate the shibboleth session.
     *
     * @param null $target URL to redirect to after successful logout. Defaults to the current request URL.
     * @return string
     */
    public function getLogoutUrl($target = null)
    {
        $request = $this->getRequest();
        if (empty($target)) {
            $target = $request->getUri();
        }
        $logoutUrl = $this->getAttribute($this->logoutUrlAttribute);
        if (!empty($logoutUrl)) {
            return $this->getHandlerUrl() . $this->getSessionLogoutPath()
            . '?return=' . urlencode($logoutUrl . (empty($target) ? '' : '?return=' . $target));
        }
        return $this->getHandlerUrl() . $this->getSessionLogoutPath() . '?return=' . urlencode($target);
    }

    /**
     * Returns URL to show session.
     *
     * @return string The absolute URL to show a session
     */
    public function getOverviewUrl()
    {
        return $this->getHandlerUrl() . $this->getSessionOverviewPath();
    }

    /**
     * Returns URL to show status.
     *
     * @return string The absolute URL to show the status
     */
    public function getStatusUrl()
    {
        return $this->getHandlerUrl() . $this->getStatusPath();
    }

    /**
     * @deprecated
     * @return string
     */
    public function getSessionInitiatorPath()
    {
        return $this->sessionLoginPath;
    }

    /**
     * @return string
     */
    public function getUsernameAttribute()
    {
        return $this->usernameAttribute;
    }

    /**
     * @return string
     */
    public function getAuthenticatedAttribute()
    {
        return $this->authenticatedAttribute;
    }

    /**
     * @return string
     */
    public function getLogoutUrlAttribute()
    {
        return $this->logoutUrlAttribute;
    }

    /**
     * @return string
     */
    public function getDefaultCharset()
    {
        return $this->defaultCharset;
    }

    /**
     * @param null|string $url
     * @return bool
     */
    public function isReachable($url = null)
    {
        if (null === $url) {
            $url = $this->getStatusUrl();
        }
        $handle = curl_init($url);
        if (false === $handle) {
            return false;
        }
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
        $succeeded = curl_exec($handle);
        curl_close($handle);
        if (false === $succeeded) {
            return false;
        }
        return true;
    }
}
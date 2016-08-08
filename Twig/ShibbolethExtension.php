<?php

namespace Kuleuven\AuthenticationBundle\Twig;

use Kuleuven\AuthenticationBundle\Service\ShibbolethServiceProvider;

class ShibbolethExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * @var ShibbolethServiceProvider
     */
    protected $shibbolethServiceProvider;

    /**
     * @var array
     */
    protected $impersonateUsers;

    /**
     * @param ShibbolethServiceProvider $shibbolethServiceProvider
     */
    public function __construct(ShibbolethServiceProvider $shibbolethServiceProvider)
    {
        $this->shibbolethServiceProvider = $shibbolethServiceProvider;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'kuleuven_authentication';
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return ['kuleuven_shibboleth' => $this->shibbolethServiceProvider];
    }
}

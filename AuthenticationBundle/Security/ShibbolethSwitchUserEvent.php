<?php

namespace Kuleuven\AuthenticationBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;

class ShibbolethSwitchUserEvent extends SwitchUserEvent
{
    /**
     * @var KuleuvenUserToken
     */
    protected $token;

    /**
     * @param Request             $request
     * @param UserInterface       $targetUser
     * @param KuleuvenUserToken $token
     */
    public function __construct(Request $request, UserInterface $targetUser, KuleuvenUserToken $token)
    {
        parent::__construct($request, $targetUser);
        $this->token = $token;
    }

    /**
     * @return KuleuvenUserToken
     */
    public function getToken()
    {
        return $this->token;
    }
}

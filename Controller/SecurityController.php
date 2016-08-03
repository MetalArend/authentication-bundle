<?php

namespace Kuleuven\AuthenticationBundle\Controller;

class SecurityController
{
    /**
     *
     */
    public function logoutAction()
    {
        throw new \RuntimeException('You must activate the logout setting in your security firewall configuration.');
    }
}

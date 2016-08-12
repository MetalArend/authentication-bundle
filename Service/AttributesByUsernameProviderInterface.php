<?php

namespace Kuleuven\AuthenticationBundle\Service;

interface AttributesByUsernameProviderInterface
{
    public function getAttributesByUsername($username);
}

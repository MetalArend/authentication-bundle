<?php

namespace Kuleuven\AuthenticationBundle\Service;

interface AttributesInjectionProviderInterface extends AttributesProviderInterface
{
    public function isEnabled();
}
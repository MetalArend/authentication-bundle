<?php

namespace Kuleuven\AuthenticationBundle\Service;

interface AttributesInjectionProviderInterface
{
    public function isEnabled();

    public function getAttributes();
}

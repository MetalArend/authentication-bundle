<?php

namespace Kuleuven\AuthenticationBundle\Traits;

use Psr\Log\LoggerInterface;

trait LoggerTrait
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $message
     * @param string $type
     */
    protected function log($message, $type = 'info')
    {
        if (null !== $this->logger) {
            $this->logger->$type($message);
        }
    }
}

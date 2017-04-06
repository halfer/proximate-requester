<?php

/**
 * Trait to add a logging facility to whatever needs it
 */

namespace Proximate;

use Psr\Log\LoggerInterface;
use Monolog\Logger as MonologLogger;

trait Logger
{
    protected $logger;

    /**
     * Sets up a PSR-compliant logger on this class
     *
     * @param LoggerInterface $logger
     * @return $this
     */
    public function addLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Logs a message if a logger has been provided, otherwise ignores log request
     *
     * @param string $message
     * @param integer $level
     */
    protected function log($message, $level = MonologLogger::INFO)
    {
        if ($logger = $this->logger)
        {
            /* @var $logger LoggerInterface */
            $logger->log($level, $message);
        }
    }
}
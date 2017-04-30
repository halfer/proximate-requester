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
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Gets the current logger or null if one is not set
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
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
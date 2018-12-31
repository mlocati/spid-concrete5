<?php

namespace SPID;

use Concrete\Core\Config\Repository\Repository;
use Psr\Log\LoggerInterface;

/**
 * SPID logger.
 */
class Logger
{
    /**
     * The configuration instance.
     *
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * The application logger.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Initialize the instance.
     *
     * @param \Concrete\Core\Config\Repository\Repository $config the configuration instance
     * @param \Psr\Log\LoggerInterface $logger the application logger
     */
    public function __construct(Repository $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function logOutboundMessage($xml)
    {
        if ($this->config->get('spid::service_provider.logMessages')) {
            $this->logger->debug($xml);
        }
    }

    public function logInboundMessage($xml)
    {
        if ($this->config->get('spid::service_provider.logMessages')) {
            $this->logger->debug($xml);
        }
    }
}

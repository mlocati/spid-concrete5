<?php

namespace SPID;

use Concrete\Core\Logging\Logger as CoreLogger;
use Monolog\Logger as MonologLogger;

/**
 * SPID logger.
 */
class Logger extends CoreLogger
{
    /**
     * The SPID configuration.
     *
     * @var \Concrete\Core\Config\Repository\Liaison
     */
    protected $config;

    /**
     * Initialize the instance.
     *
     * @param \Concrete\Core\Config\Repository\Liaison $config the SPID configuration
     */
    public function __construct($config)
    {
        $this->config = $config;
        parent::__construct('SPID', MonologLogger::DEBUG);
    }

    public function logOutboundMessage($xml)
    {
        if ($this->config->get('service_provider.logMessages')) {
            $this->addDebug($xml);
        }
    }

    public function logInboundMessage($xml)
    {
        if ($this->config->get('service_provider.logMessages')) {
            $this->addDebug($xml);
        }
    }
}

<?php

namespace Sqquid\Sync\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    private $fileName = '/var/log/sqquid_sync.log';
    private $loggerType = \Monolog\Logger::DEBUG;
}

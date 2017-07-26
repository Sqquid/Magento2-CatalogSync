<?php

namespace Sqquid\Sync\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $fileName = '/var/log/sqquid_sync.log';
    protected $loggerType = \Monolog\Logger::DEBUG;
}

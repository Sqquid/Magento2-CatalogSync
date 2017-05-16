<?php namespace Sqquid\Sync\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{

    protected $storeManager;
    protected $objectManager;

    const XML_PATH_IMPORTSETTING = 'sqquid_general/';

    public function __construct(Context $context, ObjectManagerInterface $objectManager, StoreManagerInterface $storeManager
    )
    {
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getTaxclassConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_IMPORTSETTING . 'taxclass/' . $code, $storeId);
    }

    public function getVisibilityConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_IMPORTSETTING . 'visiblity/' . $code, $storeId);
    }


    public function getStoreConfigValue($code, $storeId = null)
    {
        return $this->getConfigValue($code, $storeId);
    }

    public function convertStringToCode($groupName)
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($groupName)), '-');
    }

    public function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    public function nDigitRandom($digits)
    {
        return rand(pow(10, $digits - 1) - 1, pow(10, $digits) - 1);
    }

    public function secondsToTime($s)
    {
        $h = floor($s / 3600);
        $s -= $h * 3600;
        $m = floor($s / 60);
        $s -= $m * 60;
        return $h . ':' . sprintf('%02d', $m) . ':' . sprintf('%02d', $s);
    }
}
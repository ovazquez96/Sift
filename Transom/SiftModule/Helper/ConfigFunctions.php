<?php
/**
 * Transom Group Inc.

 *
 * @category    Transom
 * @package     Transom_Group
 * @copyright   Copyright (c) Transom Group. All rights reserved. (https://transom-group.com/)
 */

namespace Transom\SiftModule\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use \Transom\SiftModule\Logger\Logger;

class ConfigFunctions extends AbstractHelper
{

    /**
     * Logging instance
     * @var \Transom\SiftModule\Logger\Logger
     */

    protected $_logger;
    protected $scopeConfig;

    public function __construct(
        Logger $logger,
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_logger = $logger;
        $this->scopeConfig = $scopeConfig;
        // parent::__construct($context);

    }

    public function isLogEnabled($option){
        if($option){
            //Login is enabled
            $isLogEnabled = true;
        }
        else{
            $isLogEnabled = false;
        }
    }

    public function isDebugEnabled(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $option = $this->scopeConfig->getValue('transom/sift_config/debug_enabled', $storeScope);
        //   $option = $debugValue = $this->scopeConfig->getValue('transom/sift_config/debug_enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
       // $this->_logger->info("\n DEBUG CONFIG OPTION : ".$option."\n");
        if($option == '1'){
            //Debug is enabled
           return true;
        }
        else{
            return false;

        }

    }

    public function getApiKey(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $apiKey = $this->scopeConfig->getValue('transom/sift_config/sift_api_key', $storeScope);
        return $apiKey;
    }

    public function getSignatureKey(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $signatureKey = $this->scopeConfig->getValue('transom/sift_config/sift_signature_key', $storeScope);
        return $signatureKey;
    }

    public function getAccountID(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $accountID = $this->scopeConfig->getValue('transom/sift_config/sift_account_id', $storeScope);
        return $accountID;
    }

    public function getPaymentGateway(){
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $paymentGateway = $this->scopeConfig->getValue('transom/sift_config/sift_gateway', $storeScope);
        return $paymentGateway;
    }

}
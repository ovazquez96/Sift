<?php
/**
 * Transom Group Inc.

 *
 * @category    Transom
 * @package     Transom_Group
 * @copyright   Copyright (c) Transom Group. All rights reserved. (https://transom-group.com/)
 */

namespace Transom\SiftModule\Observer\Events;

use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\StoreManagerInterface;
use \Transom\SiftModule\Logger\Logger;
use \Transom\SiftModule\Helper\Data;
use \Transom\SiftModule\Helper\SiftConstants;
use \Magento\Customer\Model\Session;
use \Magento\Framework\Message\ManagerInterface;

use \Transom\SiftModule\Interfaces\EventsInterface;

class AccountLoginEvent implements ObserverInterface, EventsInterface
{

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Logger
     */
    protected $logger;
	
	/**
     * @var CustomerSession
     */
	protected $customerSession;
	
	/**
     * @var Data Helper
     */
    protected $dataHelper;
	
		/**
     * @var SiftConstants Helper
     */
    protected $constantsHelper;


    public function __construct(
        StoreManagerInterface $storeManager,
        Logger $logger,
		\Magento\Customer\Model\Session $customerSession,
		Data $dataHelper,
		SiftConstants $constantsHelper
    ) {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
		$this->customerSession = $customerSession;
		$this->dataHelper = $dataHelper;
		$this->constantsHelper = $constantsHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	    $this->logger->info('>>>> In AccountLoginEvent <<<<<');

		$helper= $this->dataHelper; //The helper contains all common functions for Sift
		
		$constants= $this->constantsHelper;
        $event = $constants::LOGIN_EVENT_NAME;
	    $this->logger->info('AccountLoginEvent; $event = '.$event);

		$customer = $observer->getEvent()->getCustomer();
        // basic properties
        $properties = $this->addBasicProperties($customer);

        // custom properties
        $customProperties = $this->addCustomProperties($customer);
        //$properties = array_merge($properties, $customProperties);

        // add options
        $options = $this->addOptions();

        // If customer data is empty then doesn't need to process
        if (!$customer) {
            return $this;
        }

		$helper->sendDataToSift($event, $properties, $options);
        $this->processDecision($customer);
    }

    public function addBasicProperties($customer){
        $helper= $this->dataHelper;
        $customer_id = $customer->getId();
        $customer_email=$customer->getEmail();
        $session = $this->customerSession->getMyValue();
        $customer_agent = $_SERVER ['HTTP_USER_AGENT'];

        // If customer data is empty then doesn't need to process
        if (!$customer) {
            return $this;
        }

        // $login event
        $properties = array(
            // Required Fields
            '$user_id'    => $customer_id,
            '$session_id'    => $customer_id,
            '$login_status' => '$success',
            '$ip' => $helper->getRemoteIp(),

            // Optional Fields
            //'$failure_reason' => '$account_unknown',
            '$username'       => $customer_email,
            '$account_types'  => ['shopper'],

            '$browser'    => array(
                '$user_agent' =>  $customer_agent
            )

        );

        return $properties;

    }

    public function addCustomProperties($customer)
    {
        // Override to add custom properties
        $customProperties = array();

        // example
        //$customProperties = array(
        //  '$session_id'       => 'session-1234-5678',
        //  '$user_email'       => 'BOBBY@EXAMPLE.COM'
        //);

        return $customProperties;
    }


    public function addOptions()
    {
        // Override to add options
        $options = array();

        return $options;
    }

    public function processDecision($customer)
    {
        $this->logger->info('In LoginEvent processDecision(), Decision Item Type: '.$this->dataHelper->getDecisionItemType().'; item id: '.$this->dataHelper->getDecisionItemId().'; decision: '.$this->dataHelper->getDecision());
        $this->logger->info('In Login processDecision(), customer id: '.$customer->getId().';');

        $this->logger->info('In Login processDecision()');

        if ( $this->dataHelper->getDecision() == 'looks_bad_payment_abuse' ) {
            $this->logger->info('In Login processDecision(), setting user to FRAUD.');
        }
    }
	
}

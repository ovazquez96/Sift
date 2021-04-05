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

class AccountLogoutEvent implements ObserverInterface
{

    /**
     * @var Logger
     */
    protected $logger;
	
	protected $customerFactory;

	/**
     * @var Data Helper
     */
    protected $dataHelper;
	
	/**
     * @var SiftConstants Helper
     */
    protected $constantsHelper;
	

    public function __construct(
        Logger $logger,
		Data $dataHelper,
		SiftConstants $constantsHelper
    ) {

        $this->logger = $logger;
		$this->dataHelper = $dataHelper;
		$this->constantsHelper = $constantsHelper;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	    $this->logger->info('>>>> In AccountLogoutEvent <<<<<');
		
		$helper= $this->dataHelper; //The helper contains all common functions for Sift
		$constants= $this->constantsHelper;
        $event = $constants::LOGOUT_EVENT_NAME;
   
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();

		$customer = $observer->getEvent()->getCustomer();
	    $customer_id = $customer->getId();
		$customer_email=$customer->getEmail();
		$customer_agent = $_SERVER ['HTTP_USER_AGENT'];

        // If customer data is empty then doesn't need to process
        if (!$customer) {
            return $this;
        }

		// $logout event
		$properties = array(
		  // Required Fields
		  '$user_id'    => $customer_id,

		  '$browser'    => array(
			'$user_agent' =>  $customer_agent
		  )
		  
		);

		$helper->sendDataToSift($event, $properties, NULL);
    }
	
}

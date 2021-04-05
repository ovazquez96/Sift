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
use Psr\Log\LoggerInterface;
use \Transom\SiftModule\Helper\Data;
use \Transom\SiftModule\Helper\SiftConstants;

use \Transom\SiftModule\Interfaces\EventsInterface;

class CreateAccountEvent implements ObserverInterface, EventsInterface
{


    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;
	/**
     * @var CustomerFactory
     */
	protected $customerFactory;
	/**
     * @var AddressFactory
     */
	protected $addressFactory;
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
        LoggerInterface $logger,
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
		
		
		$helper= $this->dataHelper; //The helper contains all commmon functions for Sift
		$constants= $this->constantsHelper;
        $event = $constants::CREATE_ACCOUNT_EVENT_NAME;

        $customer = $observer->getEvent()->getCustomer();

        // basic properties
        $properties = $this->addBasicProperties($customer);

        // custom properties
        $customProperties = $this->addCustomProperties($customer);
        //$properties = array_merge($properties, $customProperties);

        // add options
        $options = $this->addOptions();
		$helper->sendDataToSift($event, $properties, $options);
        $this->processDecision($customer);

    }


    public function addBasicProperties($customer){
        $helper= $this->dataHelper;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //Customer main info
        $customer_id = $customer->getId();
        $customer_email=$customer->getEmail();
        $first_name= $customer->getFirstname();
        $last_name= $customer->getLastname();
        $customer_name = $first_name." ".$last_name;
        $session = $this->customerSession->getMyValue();
        $referrer_user_id = $customer_id;
        $customer_agent = $_SERVER ['HTTP_USER_AGENT'];

        //Billing Address variables

        $billingID = $customer->getDefaultBilling();
        $billingAddress = $objectManager->create('Magento\Customer\Model\Address')->load($billingID);
        $billingTelephone = $billingAddress->getTelephone();
        $helper->setBillingAddress($customer,$billingAddress);

        //Shipping Address variables

        $shippingId = $customer->getDefaultShipping();
        $shippingAddress = $objectManager->create('Magento\Customer\Model\Address')->load($shippingId);
        $helper->setShippingAddress($customer,$shippingAddress);


        // If customer data is empty then doesn't need to process
        if (!$customer) {
            return $this;
        }


        // Sample $create_account event
        $properties = array(
            // Required Fields
            '$user_id'    => $customer_id,
            '$ip' => $helper->getRemoteIp(),

            // Supported Fields
            '$session_id'       => $session,
            '$user_email'       => $customer_email,
            '$name'             => $customer_name,
            '$phone'            => $billingTelephone,
            '$referrer_user_id' => $referrer_user_id,

            '$billing_address'  =>  $helper->getBillingAddress(),

            '$shipping_address' => $helper->getShippingAddress(),

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
        $options = array(
            'return_workflow_status' => True,
            'abuse_types' =>  array('payment_abuse')
        );

        return $options;
    }

    public function processDecision($customer)
    {
        $this->logger->info('In Create Account processDecision(), Decision Item Type: '.$this->dataHelper->getDecisionItemType().'; item id: '.$this->dataHelper->getDecisionItemId().'; decision: '.$this->dataHelper->getDecision());
        $this->logger->info('In Create processDecision(), customer id: '.$customer->getId().';');


        $this->logger->info('In Create processDecision()');

        if ($this->dataHelper->getDecision() == 'looks_bad_payment_abuse') {
            $this->logger->info('In Login processDecision(), setting user to FRAUD.');

            //Block customer
            $this->customerSession->logout();
            $this->messageManager->addErrorMessage(__('Your account has been locked, please contact admin'));
            $redirectionUrl = $this->url->getUrl('customer/login');
            $this->responseFactory->create()->setRedirect($redirectionUrl)->sendResponse();
            return $this;

        }
        else {
            //something
        }
    }




	
}

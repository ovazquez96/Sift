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
use  \Magento\Framework\App\RequestInterface;
class UpdateAccountEvent implements ObserverInterface
{
   

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var LoggerInterface
     */
    protected $logger;
	
	protected $customerFactory;
	protected $addressFactory;
	
	protected $customerSession;
	
	/**
     * @var Data Helper
     */
    protected $dataHelper;
	
	/**
     * @var SiftConstants Helper
     */
    protected $constantsHelper;

    protected $_request;

    public function __construct(
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
		\Magento\Customer\Model\Session $customerSession,
		Data $dataHelper,
		SiftConstants $constantsHelper,
        RequestInterface $request
    ) {
   
        $this->storeManager = $storeManager;
        $this->logger = $logger;
		$this->customerSession = $customerSession;
		$this->dataHelper = $dataHelper;
		$this->constantsHelper = $constantsHelper;
		$this->_request = $request;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

       // $post = $this->_request->getPost();
        //$newpassword = "";
        //if(isset($post["password_confirmation"])) {
          //  $newpassword = $post["password_confirmation"];
        //}
		$helper= $this->dataHelper; //The helper contains all commmon functions for Sift
		$constants= $this->constantsHelper;
        $event = $constants::UPDATE_ACCOUNT_EVENT_NAME;
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //contact info
        //$customer = $observer->getCustomerCandidateDataObject();
		//$customer = $observer->getCustomerDataObject();
        //$customer = $observer->getEvent()->getCustomer();
        $customerAddress = $observer->getCustomerAddress();
        $customer = $customerAddress->getCustomer();

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
        $billingCompany = $billingAddress->getCompany();
        $billingTelephone = $billingAddress->getTelephone();
        $billingZipcode = $billingAddress->getPostcode();
        $billingCity = $billingAddress->getCity();
        $billingRegion = $billingAddress->getRegion();
        $billingStreet = $billingAddress->getStreet();
        $billingAddress1 = $billingStreet[0];
        $billingAddress2 = "";
        if(isset($billingStreet[1])){
            $billingAddress2 = $billingStreet[1];
        }

        //Shipping Address variables
        $shippingId = $customer->getDefaultShipping();
        $shippingAddress = $objectManager->create('Magento\Customer\Model\Address')->load($shippingId);
        $shippingCompany = $shippingAddress->getCompany();
        $shippingTelephone = $shippingAddress->getTelephone();
        $shippingZipcode = $shippingAddress->getPostcode();
        $shippingCity = $shippingAddress->getCity();
        $shippingRegion = $shippingAddress->getRegion();
        $shippingStreet = $shippingAddress->getStreet();
        $shippingAddress1 = $shippingStreet[0];
        $shippingAddress2 = "";
        if(isset($shippingStreet[1])){
            $shippingAddress2 = $shippingStreet[1];
        }


		
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

			'$changed_password' => True,
		  '$user_email'       => $customer_email,
		  '$name'             => $customer_name,
		  '$phone'            => $billingTelephone,
		  '$referrer_user_id' => $referrer_user_id,


            '$billing_address'  => array(
                '$name'         => $customer_name,
                '$phone'        => $shippingTelephone,
                '$address_1'    => $shippingAddress1,
                '$address_2'    => $shippingAddress2,
                '$city'         => $shippingCity,
                '$region'       => $shippingRegion,
                '$country'      => 'US',
                '$zipcode'      => $shippingZipcode
            ),

		  '$shipping_address' => array(
              '$name'         => $customer_name,
              '$phone'        => $shippingTelephone,
              '$address_1'    => $shippingAddress1,
              '$address_2'    => $shippingAddress2,
              '$city'         => $shippingCity,
              '$region'       => $shippingRegion,
              '$country'      => 'US',
              '$zipcode'      => $shippingZipcode
		  ),
		  
		  '$browser'    => array(
			'$user_agent' =>  $customer_agent
		  )
		  
		);

		$helper->sendDataToSift($event, $properties, NULL);

       
    }
	
}

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
use  \Magento\Framework\Encryption\EncryptorInterface;
use \Magento\Framework\ObjectManagerInterface;
use \Magento\Framework\App\RequestInterface;


class UpdateAccountAddress implements ObserverInterface
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

    protected $encryptor;
    protected  $objectM;
    protected  $_request;
    public function __construct(
      
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
		\Magento\Customer\Model\Session $customerSession,
		Data $dataHelper,
		SiftConstants $constantsHelper,
        EncryptorInterface $encryptor,
        ObjectManagerInterface $objectM,
        RequestInterface $request
    ) {
    
        $this->storeManager = $storeManager;
        $this->logger = $logger;
		$this->customerSession = $customerSession;
		$this->dataHelper = $dataHelper;
		$this->constantsHelper = $constantsHelper;
        $this->encryptor = $encryptor;
        $this->objectM = $objectM;
        $this->_request = $request;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {


		$helper= $this->dataHelper; //The helper contains all commmon functions for Sift
		$constants= $this->constantsHelper;
        $event = $constants::UPDATE_ACCOUNT_EVENT_NAME;
		
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
	  
	    //$customer = $observer->getEvent()->getCustomer();
		$customerAddress = $observer->getCustomerAddress();
        $customer = $customerAddress->getCustomer();
		
		//Password hash

        $post = $this->_request->getPost();
        
        $passwordChanged = false;

        if(isset($post["password_confirmation"])){

            $passwordNew = $post["password_confirmation"];
            $currentPasswordHash = $this->getCurrentPasswordHash($customer->getEntityId());

            try{
                $newPasswordHash = $this->encryptor->encrypt($passwordNew);
                if($currentPasswordHash == $newPasswordHash){
                    // password is same
                    $passwordChanged = false;

                }
                else if ($currentPasswordHash != $newPasswordHash){
                    $passwordChanged = true;
                }
            }catch(\Exception $e){
                echo 'Error::'.$e->getMessage();
            }

        }


		
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
		  '$changed_password' => $passwordChanged,
			'$session_id'       => $session,
		  '$user_email'       => $customer_email,
		  '$name'             => $customer_name,
		  '$phone'            => $billingTelephone,
		  '$referrer_user_id' => $referrer_user_id,
		 
		  '$billing_address'  => array(
			  '$name'         => $customer_name,
			  '$phone'        => $billingTelephone,
			  '$address_1'    => $billingAddress1,
			  '$address_2'    => $billingAddress2,
			  '$city'         => $billingCity,
			  '$region'       => $billingRegion,
			  '$country'      => 'US',
			  '$zipcode'      => $billingZipcode
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

        $opts = array(
            'return_workflow_status' => True,
            'abuse_types' =>  array('payment_abuse')
        );

		$helper->sendDataToSift($event, $properties,  $opts );

    }

    private function getCurrentPasswordHash($customerId){
        $resource = $this->objectM->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $sql = "Select password_hash from customer_entity WHERE entity_id = ".$customerId;
        $hash = $connection->fetchOne($sql);
        return $hash;
    }
	
}

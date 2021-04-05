<?php

namespace Transom\SiftModule\Observer\Events;

use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use \Transom\SiftModule\Logger\Logger;
use \Transom\SiftModule\Helper\Data;
use \Transom\SiftModule\Helper\SiftConstants;
use \Transom\SiftModule\Helper\ConfigFunctions;
use \Transom\SiftModule\Interfaces\EventsInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class CreateOrderEvent implements ObserverInterface, EventsInterface
{
    /**
     * @var LoggerInterface
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

    /**
     * @var ConfigFunctions Helper
     */
    protected $configFunctions;
	
	/**
     * @var OrderRepositoryInterface
     */
	 protected $_orderRepository;


    /**
     * @param Logger $logger
     * @param Session $customerSession
     * @param Data $dataHelper
     * @param SiftConstants $constantsHelper
     * @param ConfigFunctions $configFunctions
     */
    public function __construct(
        Logger $logger,
        Session $customerSession,
        Data $dataHelper,
        SiftConstants $constantsHelper,
        ConfigFunctions $configFunctions,
		OrderRepositoryInterface $orderRepository
    ) {
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        $this->constantsHelper = $constantsHelper;
        $this->configFunctions = $configFunctions;
		 $this->_orderRepository = $orderRepository;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $this->logger->info('Start CreateOrderEvent');

		$helper= $this->dataHelper;
        $constants= $this->constantsHelper;

        // get order
	    $order = $observer->getEvent()->getOrder();

        // If order data is empty then doesn't need to process
        if (empty($order)) {
            $this->logger->info('There is an error in CreateOrderEvent');
            return $this;
        }

        // basic properties
        $properties = $this->addBasicProperties($order);

        // custom properties
        $customProperties = $this->addCustomProperties($order);
        $properties = array_merge($properties, $customProperties);

        // add options
        $options = $this->addOptions();

		// send data to Sift console
		$helper->sendDataToSift($constants::CREATE_ORDER_EVENT_NAME, $properties, $options);

		$this->processDecision($order);

		$this->logger->info('End CreateOrderEvent');
    }


    /**
     * @param  Order $order
     */
    public function addBasicProperties($order)
    {

	    //Order main info
	    $order_id           = $order->getIncrementId();
	    $order_amount       = $this->dataHelper->convertAmountToMicros($order->getGrandTotal());
	    $order_currency     = $order->getOrderCurrencyCode();

        //Customer main info
        $customer_id        = $order->getCustomerId();
        $customer_email     = $order->getCustomerEmail();
        $session            = $this->customerSession->getMyValue();
        $customer_agent     = $_SERVER ['HTTP_USER_AGENT'];

		//Billing Address details
		$billingAddress     = $order->getBillingAddress();
		$billingFirstName   = $billingAddress->getFirstname();
		$billingLastName    = $billingAddress->getLastName();
		$billingName        = $billingFirstName." ".$billingLastName;
        $billingTelephone   = $billingAddress->getTelephone();
        $billingStreet      = $billingAddress->getStreet();
        $billingAddress1    = $billingStreet[0];
        $billingAddress2    = "";
        if(isset($billingStreet[1])){
            $billingAddress2 = $billingStreet[1];
        }
		$billingCity        = $billingAddress->getCity();
		$billingRegion      = $billingAddress->getRegion();
		$billingCountry     = $billingAddress->getCountryId();
        $billingZipCode     = $billingAddress->getPostcode();

		//Shipping Address details
		$shippingAddress    = $order->getShippingAddress();
        $shippingFirstName  = $shippingAddress->getFirstname();
        $shippingLastName   = $shippingAddress->getLastName();
        $shippingName       = $shippingFirstName." ".$shippingLastName;
        $shippingTelephone  = $shippingAddress->getTelephone();
        $shippingStreet     = $shippingAddress->getStreet();
        $shippingAddress1   = $shippingStreet[0];
        $shippingAddress2   = "";
        if(isset($shippingStreet[1])){
            $shippingAddress2 = $shippingStreet[1];
        }
        $shippingCity       = $shippingAddress->getCity();
        $shippingRegion     = $shippingAddress->getRegion();
        $shippingCountry    = $shippingAddress->getCountryId();
        $shippingZipCode    = $shippingAddress->getPostcode();

        //Payment gateway from Admin panel
        $paymentType        = $this->configFunctions->getPaymentGateway();
        $payment            = $order->getPayment();
        $cardLast4          = $payment->getCcLast4();

        //Order items details
        $orderItems         = $order->getAllVisibleItems();
        $items = [];
        foreach ($orderItems as $item){

            //Create array
            $tempItems = array();
            $tempItems = array(
                '$sku'            => $item->getProductId(),
                '$product_title'  => $item->getName(),
                '$price'          => $this->dataHelper->convertAmountToMicros($item->getPrice()),
                '$quantity'       => intval($item->getQtyOrdered()),
                '$currency_code'  => $order_currency
            );

            array_push($items, $tempItems);
        }

        if (!empty($payment->getAmountAuthorized())) {
            $paymentMethods = array(
                array(
                    '$payment_type'    => '$credit_card',
                    '$payment_gateway' => $paymentType
                )
            );
        } else {
            // TODO: add proper logic all payment types
            $paymentMethods = array(
                array(
                    '$payment_type'    => '$cash'
                )
            );
        }

		// set up the create order event data
		$basicProperties = array(

		  // Required Fields
		  '$user_id'          => $customer_id,
          '$ip'               => $this->dataHelper->getRemoteIp(),

		  // Supported Fields
		  '$order_id'         => $order_id,
		  '$user_email'       => $customer_email,
		  '$amount'           => $order_amount,
          '$currency_code'    => $order_currency,
		  '$billing_address'  => array(
			  '$name'         => $billingName,
			  '$phone'        => $billingTelephone,
			  '$address_1'    => $billingAddress1,
			  '$address_2'    => $billingAddress2,
			  '$city'         => $billingCity,
			  '$region'       => $billingRegion,
			  '$country'      => $billingCountry,
			  '$zipcode'      => $billingZipCode
		  ),
		  '$shipping_address' => array(
			  '$name'         => $shippingName,
			  '$phone'        => $shippingTelephone,
			  '$address_1'    => $shippingAddress1,
			  '$address_2'    => $shippingAddress2,
			  '$city'         => $shippingCity,
			  '$region'       => $shippingRegion,
			  '$country'      => $shippingCountry,
			  '$zipcode'      => $shippingZipCode
		  ),
		  '$payment_methods'  => $paymentMethods,
          '$items'            => $items,
		  '$browser'          => array(
		      '$user_agent'     =>  $customer_agent
		  )
		);

		return $basicProperties;
    }


    /**
     * @param  Order $order
     */
    public function addCustomProperties($order)
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


    /**
     *
     */
    public function addOptions()
    {
        // Override to add options
        $options = array(
            'return_workflow_status' => True,
            'abuse_types' =>  array('payment_abuse')
        );

		return $options;
    }


    /**
     * @param  Order $order
     *
     */
    public function processDecision($order)
    {
		$orderId = $order->getIncrementId();
		
        $this->logger->info('In CreateOrderEvent processDecision(), Decision Item Type: '.$this->dataHelper->getDecisionItemType().'; item id: '.$this->dataHelper->getDecisionItemId().'; decision: '.$this->dataHelper->getDecision());
        $this->logger->info('In CreateOrderEvent processDecision(), order id: '.$order->getIncrementId().'; orderState: '.$order->getState().'; orderStatus: '.$order->getStatus());

        $orderState = $order->getState();
        $this->logger->info('In CreateOrderEvent processDecision(), orderState: '.$orderState);
        if ( $this->dataHelper->getDecision() == 'order_looks_bad_payment_abuse' ) {
            $this->logger->info('In CreateOrderEvent processDecision(), setting orderState to FRAUD.');
			
            /**TODO
             *-Set a custom property called siftDecision
             * find the right state/status for the order
            **/
			
		 try{
            
			$order->setState(\Magento\Sales\Model\Order::STATE_HOLDED)->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD);
			$order->save();
           
			
			$this->logger->info('AFTER FRAUD ORDER order id: ; orderState: '.$order->getState().'; orderStatus: '.$order->getStatus());
			
            return true;
        } catch (\Exception $e){
            // add some logging here
            return false;
        }
			
			
        }
    }
	
	
	
}
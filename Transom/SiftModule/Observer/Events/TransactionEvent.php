<?php

namespace Transom\SiftModule\Observer\Events;

use Magento\Framework\Event\ObserverInterface;
use \Transom\SiftModule\Logger\Logger;
use \Transom\SiftModule\Helper\Data;
use Magento\Sales\Model\Order;
use \Transom\SiftModule\Helper\ConfigFunctions;
use \Transom\SiftModule\Helper\SiftConstants;

class TransactionEvent implements ObserverInterface
{

    /**
     * @var Logger
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
     * @param Logger $logger
     * @param Data $dataHelper
     * @param SiftConstants $constantsHelper
     * @param ConfigFunctions $configFunctions
     */
    public function __construct(
        Logger $logger,
		Data $dataHelper,
		SiftConstants $constantsHelper,
        ConfigFunctions $configFunctions
    ) {
        $this->logger = $logger;
		$this->dataHelper = $dataHelper;
		$this->constantsHelper = $constantsHelper;
        $this->configFunctions = $configFunctions;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
	    $this->logger->info('>>>>> In TransactionEvent <<<<<');

		$helper= $this->dataHelper;
        $event = $this->constantsHelper::TRANSACTION_EVENT_NAME;

        // get payment and order
        $payment = $observer->getData('payment');
        $order = $payment->getOrder();

        if (!empty($payment->getAmountAuthorized())) {
            $paymentGateway      = $this->configFunctions->getPaymentGateway();
            $paymentMethod = array(
                '$payment_type'    => '$credit_card',
                '$payment_gateway' => $paymentGateway,
                '$card_last4'      => $payment->getCcLast4()
            );
            $transactionType = '$authorize';
        } else {
            $transactionType = '$sale';
            // TODO: add proper logic all payment types
            $paymentMethod = array(
                '$payment_type'    => '$cash'
            );
        }

        $billingAddress=$order->getBillingAddress();
        $billingFirstName   = $billingAddress->getFirstName();
        $billingLastName    = $billingAddress->getLastName();
        $billingName        = $billingFirstName." ".$billingLastName;
        $billingStreet      = $billingAddress->getStreet();
        $billingAddress1    = $billingStreet[0];
        $billingAddress2    = "";
        if(isset($billingStreet[1])){
            $billingAddress2 = $billingStreet[1];
        }

        $shippingAddress=$order->getBillingAddress();
        $shippingFirstName   = $shippingAddress->getFirstName();
        $shippingLastName    = $shippingAddress->getLastName();
        $shippingName        = $shippingFirstName." ".$shippingLastName;
        $shippingStreet      = $shippingAddress->getStreet();
        $shippingAddress1    = $shippingStreet[0];
        $shippingAddress2    = "";
        if(isset($shippingStreet[1])){
            $shippingAddress2 = $shippingStreet[1];
        }

        $properties = array(
            // Required Fields
            '$user_id'            => $order->getCustomerId(),
            '$amount'             => $helper->convertAmountToMicros($payment->getAmountAuthorized()),
            '$currency_code'      => $order->getOrderCurrencyCode(),

            // Supported Fields
            '$user_email'         => $order->getCustomerEmail(),
            '$transaction_type'   => $transactionType,
            '$transaction_status' => '$success',
            '$order_id'           => $order->getIncrementId(),
            '$transaction_id'     => $payment->getTransactionId(),

            '$payment_method'     => $paymentMethod,

            '$billing_address'  => array(
                '$name'         => $billingName,
                '$phone'        => $billingAddress->getTelephone(),
                '$address_1'    => $billingAddress1,
                '$address_2'    => $billingAddress2,
                '$city'         => $billingAddress->getCity(),
                '$region'       => $billingAddress->getRegion(),
                '$country'      => $billingAddress->getCountryId(),
                '$zipcode'      => $billingAddress->getPostcode()
            ),

            '$shipping_address' => array(
                '$name'         => $shippingName,
                '$phone'        => $shippingAddress->getTelephone(),
                '$address_1'    => $shippingAddress1,
                '$address_2'    => $shippingAddress2,
                '$city'         => $shippingAddress->getCity(),
                '$region'       => $shippingAddress->getRegion(),
                '$country'      => $shippingAddress->getCountryId(),
                '$zipcode'      => $shippingAddress->getPostcode()
            ),

            '$browser'    => array(
                '$user_agent' =>  $_SERVER ['HTTP_USER_AGENT']
            )

		);

		$helper->sendDataToSift($event, $properties, NULL);

        $this->logger->info('>>>>> End TransactionEvent <<<<<');
    }
	
}

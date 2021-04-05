<?php

namespace Transom\SiftModule\Helper;
use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Store\Model\StoreManagerInterface;
use \Transom\SiftModule\Logger\Logger;
use \Magento\Framework\Serialize\Serializer\Json;
use \Transom\SiftModule\Helper\SiftConstants;


class Data extends AbstractHelper
{

    /**
     * Store Manager instance
     * @var \Magento\Store\Model\StoreManagerInterface
     */

    protected $_storeManager;

    /**
     * Logging instance
     * @var \Transom\SiftModule\Logger\Logger
     */

    protected $_logger;


    /**
     * Json instance
     * @var \Magento\Framework\Serialize\Serializer\Json;
     */

    protected $_json;

    /**
     * Json Helper instance
     * @var  \Magento\Framework\Json\Helper\Data;
     */

    protected $_jsonHelper;

    /**
     * Contstants Helper instance
     * @var  \Transom\SiftModule\Helper\SiftConstants;
     */

    protected $_constantsHelper;
    protected $_configFunctions;

    //Array for Billing and Shipping Address
    public $billingAddress;
    public $shippingAddress;

    // decision
    public $decision;
    public $decisionItemType;
    public $decisionItemId;

    public function __construct(
        StoreManagerInterface $storeManager,
        Logger $logger,
        Json $json,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        SiftConstants $constantsHelper,
        ConfigFunctions $configFunctions
    ) {
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
        $this->_json = $json;
        $this->_jsonHelper = $jsonHelper;
        $this->_constantsHelper = $constantsHelper;
        $this->_configFunctions = $configFunctions;
    }


    public function getBaseUrl() {
        return $this->_storeManager->getStore()->getBaseUrl();
    }

    public function setBillingAddress($customer, $billingAddress){

        $first_name= $customer->getFirstname();
        $last_name= $customer->getLastname();
        $customer_name = $first_name." ".$last_name;

        //Billing Address variables

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

        $this->billingAddress = array(
            '$name'         => $customer_name,
            '$phone'        => $billingTelephone,
            '$address_1'    => $billingAddress1,
            '$address_2'    => $billingAddress2,
            '$city'         => $billingCity,
            '$region'       => $billingRegion,
            '$country'      => 'US',
            '$zipcode'      => $billingZipcode
        );

    }

    public function setShippingAddress($customer, $shippingAddress){


        $first_name= $customer->getFirstname();
        $last_name= $customer->getLastname();
        $customer_name = $first_name." ".$last_name;

        //Shipping Address variables
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

        $this->shippingAddress = array(
            '$name'         => $customer_name,
            '$phone'        => $shippingTelephone,
            '$address_1'    => $shippingAddress1,
            '$address_2'    => $shippingAddress2,
            '$city'         => $shippingCity,
            '$region'       => $shippingRegion,
            '$country'      => 'US',
            '$zipcode'      => $shippingZipcode
        );

    }

    public function getBillingAddress(){
        return $this->billingAddress;
    }

    public function getShippingAddress(){
        return $this->shippingAddress;
    }

    public function getDecision(){
        return $this->decision;
    }

    public function getDecisionItemType(){
        return $this->decisionItemType;
    }

    public function getDecisionItemId(){
        return $this->decisionItemId;
    }

    public function getSiftClient(){
        $apiKey = $this->_configFunctions->getApiKey();
        $accountId = $this->_configFunctions->getAccountID();

        $client = "";
        try {
            $client = new \SiftClient(array('api_key' => $apiKey, 'account_id' => $accountId));
        }
        catch(\Exception $e){
            $this->_logger->critical($e->getMessage());
        }
        return $client;
    }


    /**
     *
     */
    public function sendDataToSift($event, $properties, $opts){

        $this->_logger->info(">>>>> In sendDataToSift; event = ".$event." <<<<<");

		//$this->testFunction();

        //Connect to Sift client
        $client = $this->getSiftClient();

        if(!empty($properties)){ //IF Properties has info
            if( $this->_configFunctions->isDebugEnabled()){ //IF Debug is enabled
                $this->_logger->info("\n Data Sent \n Event: ".$event."\n".print_r($properties,true));
            }
            if (!empty($opts)) {
                $response = $client->track($event, $properties, $opts);
            } else {
                $response = $client->track($event, $properties);
            }

            $encodedData = $this->_jsonHelper->jsonEncode($response);
            $decodedData = $this->_jsonHelper->jsonDecode($encodedData,true);

            if($decodedData["httpStatusCode"] == "200"){ //IF status code is Success (200)
                if($this->_configFunctions->isDebugEnabled()) { //IF Debug is enabled
                    $this->_logger->info("\n Response \n Event: ".$event."\n".print_r($decodedData["body"],true));
                }
                if (!empty($decodedData["body"])) { //IF response body is not empty
                    $this->processScores($decodedData["body"]);
                    $this->processWorkflowStatuses($decodedData["body"]);
                }
            }
            else{
                $this->_logger->info("Status code: ".$decodedData["httpStatusCode"]);
                //$this->_logger->info('Could not establish connection with Sift, check that your API Key is correct');
                $this->_logger->info("\n Response \n  "."\n".print_r($decodedData,true));
            }

        } else {
            $this->_logger->info('Properties array is empty or missing information');
        }
    }


    /**
     *
     */
    public function processScores($responseBody){
        if (!empty($responseBody['score_response']['scores'])) {
            $scores = $responseBody['score_response']['scores'];
            if (!empty($scores['payment_abuse']['score'])) {
                $this->processPaymentAbuseScore($scores['payment_abuse']['score']);
            }
            if (!empty($scores['promo_abuse']['score'])) {
                $this->processPromoAbuseScore($scores['promo_abuse']['score']);
            }
        }
    }


    /**
     *
     */
    public function processWorkflowStatuses($responseBody){
        if (!empty($responseBody['score_response']['workflow_statuses'])) {
            $wfStatuses = $responseBody['score_response']['workflow_statuses'];
            foreach ($wfStatuses as $wfStatus) {
                if (!empty($wfStatus['entity']['type'])) {
                    $decisionType = $wfStatus['entity']['type'];
                    $itemId =  $wfStatus['entity']['id'];
                    $wfStatusHistory = $wfStatus['history'];
                    foreach ($wfStatusHistory as $history) {
                        $decisionId = "";
                        if (!empty( $history['config']['decision_id'])) {
                            $decisionId =  $history['config']['decision_id'];
                        }
                        if (!empty($history['config']['buttons'])) {
                            $decisionId = $history['config']['buttons'][0]['id'];
                        }

                        if (!empty($decisionId)) {
                            $this->_logger->info('Decision Type: '.$decisionType.'; item id: '.$itemId.'; decision id: '.$decisionId);
                            //$this->processDecision($decisionType, $itemId, $decisionId);
                            $this->decision = $decisionId;
                            $this->decisionItemType = $decisionType;
                            $this->decisionItemId = $itemId;
                        }
                    }
                }
            }
        }
    }


    /**
     *
     */
    public function processWebhook($requestBody){

        // get the json, entity type, decision id
        $webhookData = $this->_jsonHelper->jsonDecode($requestBody, true);

        $itemType = "";
        if (!empty($webhookData['entity']['type'])) {
            $itemType = $webhookData['entity']['type'];
        }

        $itemId = "";
        if (!empty($webhookData['entity']['id'])) {
            $itemId = $webhookData['entity']['id'];
        }

        $decisionId = "";
        if (!empty($webhookData['decision']['id'])) {
            $decisionId = $webhookData['decision']['id'];
        }

        if (!empty($itemType) && !empty($decisionId)) {
            $this->_logger->info('In processWebhook, Item Type: '.$itemType.'; item id: '.$itemId.'; decision id: '.$decisionId);
            //$this->processDecision($itemType, $itemId, $decisionId);
            $this->decision = $decisionId;
            $this->decisionItemType = $itemType;
            $this->decisionItemId = $itemId;
        }
    }


    /**
     *
     */
    public function processPaymentAbuseScore($paymentAbuseScore){
         $this->_logger->info('In processPaymentAbuseScore(), Payment Abuse Score: '.$paymentAbuseScore);
    }


    /**
     *
     */
    public function processPromoAbuseScore($promoAbuseScore){
         $this->_logger->info('In processPromoAbuseScore(), Promo Abuse Score: '.$promoAbuseScore);
    }


    /**
     *
     */
    public function processDecision($itemType, $itemId, $decision){
        $this->_logger->info('In processDecision, Item Type: '.$itemType.'; item id: '.$itemId.'; decision: '.$decision);

        // TODO, move order processing to CreateOrderEvent.php
        /*
        if ($itemType == 'order') {
           $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $objectManager->create('\Magento\Sales\Model\OrderRepository')->get($itemId);
            $orderState = $order->getState();

            switch ($decision) {
                case 'order_looks_ok_payment_abuse':
                    if ($orderState == \Magento\Sales\Model\Order::STATUS_FRAUD) {
                        $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
                        $order->setStatus(\Magento\Sales\Model\Order::STATE_PROCESSING, true)->save();
                    }
                    break;

                case 'order_looks_bad_payment_abuse':
                    if ($orderState == \Magento\Sales\Model\Order::STATE_PROCESSING) {
                        $order->setState(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
                        $order->setStatus(\Magento\Sales\Model\Order::STATUS_FRAUD, true)->save();
                    }
                    break;
            }
        }
        */

        if ($itemType == 'user') {
        }

    }


    /**
     *
     * @return string
     */
    public function getRemoteIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $remote_ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $remote_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $remote_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $remote_ip;
    }

    /**
     * @param $orderAmount
     * @return float|int
     */
    public function convertAmountToMicros($amount){

        if(is_numeric($amount) && $amount > 0){
            $amountInMicros = $amount * 1000000;
            return $amountInMicros;
        }
        else{
            return $amount;
        }
    }
	

	public function testFunction(){
        //test function that does nothing.
        $this->_logger->info("Actual function from Data.php");
    }

}
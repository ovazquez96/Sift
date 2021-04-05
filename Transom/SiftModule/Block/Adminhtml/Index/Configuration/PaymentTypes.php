<?php
namespace Transom\SiftModule\Block\Adminhtml\Index\Configuration;



/**
 * Class Ranges
 */
class PaymentTypes implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Prepare rendering the new field by adding all the needed columns
     */


    public function __construct()
    {

    }

    public function toOptionArray()
    {

        return [
            ['value' => '$cash', 'label' => __('Cash')],
            ['value' => '$check', 'label' => __('Check')],
            ['value' => '$credit_card', 'label' => __('Credit Card')],
            ['value' => '$crypto_currency', 'label' => __('Crypto Currency')],
            ['value' => '$digital_wallet', 'label' => __('Digital Wallet')],
            ['value' => '$electronic_fund_transfer', 'label' => __('Electronic Fund Transfer')],
            ['value' => '$financing', 'label' => __('Financing')],
            ['value' => '$gift_card', 'label' => __('Gift Card')],
            ['value' => '$invoice', 'label' => __('Invoice')],
            ['value' => '$in_app_purchase', 'label' => __('In App Purchase')],
            ['value' => '$money_order', 'label' => __('Money Order')],
            ['value' => '$points', 'label' => __('Points')],
            ['value' => '$store_credit', 'label' => __('Store Credit')],
            ['value' => '$third_party_processor', 'label' => __('Third Party Processor')],
            ['value' => '$voucher ', 'label' => __('Voucher')],
        ];
    }
}
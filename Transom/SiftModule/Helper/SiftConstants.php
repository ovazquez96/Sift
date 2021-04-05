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

class SiftConstants extends AbstractHelper
{

    //Customer events
    const CREATE_ACCOUNT_EVENT_NAME = '$create_account';
    const UPDATE_ACCOUNT_EVENT_NAME = '$update_account';
    const LOGIN_EVENT_NAME          = '$login';
    const LOGOUT_EVENT_NAME         = '$logout';

    //Order events
    const CREATE_ORDER_EVENT_NAME 	= '$create_order';
    const TRANSACTION_EVENT_NAME 	= '$transaction';

    public function __construct(
        Context $context
    ) {
        parent::__construct($context);

    }


}
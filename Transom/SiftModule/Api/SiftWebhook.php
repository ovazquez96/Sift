<?php
namespace Transom\SiftModule\Api;

/*
 * https://www.mageplaza.com/devdocs/magento-2-create-api/
 */

interface SiftWebhook {

	/**
	 * POST for Post api
	 *
	 * @return string
	 */

	public function getPost();
}
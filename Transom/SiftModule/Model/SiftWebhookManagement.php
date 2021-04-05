<?php
namespace Transom\SiftModule\Model;

class SiftWebhookManagement {

    /**
     * ConfigFunctions instance
     * @var \Transom\SiftModule\Helper\ConfigFunctions
     */
    protected $configFunctions;

    /**
     * Sift Helper instance
     * @var  \Transom\SiftModule\Helper\Data;
     */
    protected $siftHelper;

    public function __construct(
        \Transom\SiftModule\Helper\ConfigFunctions $configFunctions,
        \Transom\SiftModule\Helper\Data $siftHelper
    ) {
        $this->configFunctions = $configFunctions;
        $this->siftHelper = $siftHelper;
    }

	/**
	 * {@inheritdoc}
	 */
	public function getPost()
	{
        // get Sift signature from header
        $headers = getallheaders();
        $webhookSignature = $headers['X-Sift-Science-Signature'];

        // get request, assemble the signature
        $requestBody = file_get_contents('php://input');
        $signatureKey = $this->configFunctions->getSignatureKey();
        $verificationSignature  = "sha1=" . hash_hmac('sha1', $requestBody, $signatureKey);

        // verify signature, call process webhook
        if (strcmp($webhookSignature, $verificationSignature)==0) {
            $this->siftHelper->processWebhook($requestBody);
        }
		return null;
	}
}
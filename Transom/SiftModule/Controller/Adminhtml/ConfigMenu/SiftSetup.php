<?php

namespace Transom\SiftModule\Controller\Adminhtml\ConfigMenu;

use \Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use \Magento\Framework\Json\Helper\Data;

class SiftSetup extends \Magento\Backend\App\Action
{
    protected $resultPageFactory;
    protected $jsonHelper;

    public function __construct(Context $context, PageFactory $pageFactory, Data $jsonHelper )
    {
        $this->resultPageFactory = $pageFactory;
        $this->jsonHelper = $jsonHelper;
        parent::__construct($context);
    }

    public function execute()
    {

        $resultPage = $this->resultPageFactory->create();



		 $this->getRequest()->setParam('section','sift');
		 $resultPage->setActiveMenu('Transom_SiftModule::menu_item');
         $resultPage->addBreadcrumb(__('Sift Configuration'),__('Sift Setup'));
		 $resultPage->getConfig()->getTitle()->prepend(__('Sift Configuration'));

        return $resultPage;

    }
}
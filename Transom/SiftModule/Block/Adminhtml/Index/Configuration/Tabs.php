<?php

namespace Transom\SiftModule\Block\Adminhtml\Index\Configuration;

class Tabs extends \Magento\Backend\Block\Widget\Tabs {
    public function __construct(){
        parent::__construct();
        $this->setId('config_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Configuration'));
    }

    public function _prepareLayout(){
        $this->addTab(
            'behavior', [
                'label' => __('Configuration'),
            ]

        );

    }
}
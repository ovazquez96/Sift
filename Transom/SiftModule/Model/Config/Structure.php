<?php

namespace Transom\SiftModule\Model\Config;

class Structure extends \Magento\Config\Model\Config\Structure {
    public function __construct(
        \Transom\SiftModule\Model\Config\Structure\Data $structureData,
        \Magento\Config\Model\Config\Structure\Element\Iterator\Tab $tabIterator,
        \Magento\Config\Model\Config\Structure\Element\FlyweightFactory $flyweightFactory,
        \Magento\Config\Model\Config\ScopeDefiner $scopeDefiner


    ){
        parent::__construct($structureData, $tabIterator, $flyweightFactory, $scopeDefiner);

    }


}
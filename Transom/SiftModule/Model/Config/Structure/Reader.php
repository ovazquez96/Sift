<?php

namespace Transom\SiftModule\Model\Config\Structure;

class Reader extends \Magento\Config\Model\Config\Structure\Reader {
    public function __construct(
        \Magento\Framework\Config\FileResolverInterface $fileResolver,
        \Magento\Config\Model\Config\Structure\Converter $converter,
        \Magento\Config\Model\Config\SchemaLocator $schemaLocator,
        \Magento\Framework\Config\ValidationStateInterface $validationState,
        \Magento\Framework\View\TemplateEngine\Xhtml\CompilerInterface $compiler,
        $fileName = '',
        $idAttributes =  [],
        $domDocumentClass = '\Magento\Framework\Config\Dom',
        $defaultScope = 'global'

    ){
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $compiler,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );

    }


}
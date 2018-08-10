<?php
namespace Emipro\Importexportproductreviews\Block\Adminhtml;

class Importexport extends \Magento\Framework\View\Element\Template
{

    private $formKey;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Data\Form\FormKey $formKey
    ) {
        $this->formKey = $formKey;
        parent::__construct($context);
    }

    public function getExporturl()
    {
        return $this->_urlBuilder->getUrl("importexportproductreviews/index/exportreview");
    }
    public function getDownloadurl()
    {
        return $this->_urlBuilder->getUrl("importexportproductreviews/index/downloadcsv");
    }
    public function getImporturl()
    {
        return $this->_urlBuilder->getUrl("importexportproductreviews/index/import");
    }
    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }
}

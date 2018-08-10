<?php
/**
 * Copyright © Emipro Technologies Pvt Ltd. All rights reserved.
 * @license http://shop.emiprotechnologies.com/license-agreement/
 */
namespace Emipro\Importexportproductreviews\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action
{
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $toDate = $this->getRequest()->getPostValue("to_date");
        $fromDate = $this->getRequest()->getPostValue("from_date");
        if ($this->getRequest()->getPostValue('reviewaction') == "export") {
            $parameters = ['to_date' => $toDate, 'from_date' => $fromDate];
            $this->_forward('Exportreview', null, null, $parameters);
        } elseif ($this->getRequest()->getPostValue('reviewaction') == "import") {
            $this->_forward('Import', null, null, $data);
        }
    }
}

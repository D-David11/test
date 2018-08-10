<?php
/**
 * Copyright © Emipro Technologies Pvt Ltd. All rights reserved.
 * @license http://shop.emiprotechnologies.com/license-agreement/
 */

namespace Emipro\Importexportproductreviews\Controller\Adminhtml\Index;

class Index extends \Magento\Backend\App\Action
{
    private $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $this->messageManager->addNotice(__("Please Download Sample Data CSV Before Import.!!!"));
        $resultPage->getConfig()->getTitle()->prepend(__('Import/Export Product Reviews'));
        return $resultPage;
    }
}

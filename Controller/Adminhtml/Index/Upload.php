<?php

namespace Emipro\Importexportproductreviews\Controller\Adminhtml\Index;

use Magento\Framework\Controller\ResultFactory;

class Upload extends \Magento\Backend\App\Action
{
    private $fileUploader;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Emipro\Importexportproductreviews\Model\FileUploader $fileUploader
    ) {
        parent::__construct($context);
        $this->fileUploader = $fileUploader;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Emipro_Importexportproductreviews::Importexportproductreviews');
    }

    public function execute()
    {
        try {
            $result = $this->fileUploader->saveFileToTmpDir('csv_import');
            $result['cookie'] = [
                'name' => $this->_getSession()->getName(),
                'value' => $this->_getSession()->getSessionId(),
                'lifetime' => $this->_getSession()->getCookieLifetime(),
                'path' => $this->_getSession()->getCookiePath(),
                'domain' => $this->_getSession()->getCookieDomain(),
            ];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}

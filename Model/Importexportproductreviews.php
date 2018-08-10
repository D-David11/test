<?php
/**
 * Copyright © Emipro Technologies Pvt Ltd. All rights reserved.
 * @license http://shop.emiprotechnologies.com/license-agreement/
 */
namespace Emipro\Importexportproductreviews\Model;

use Emipro\Importexportproductreviews\Model\ResourceModel\Importexportproductreviews as EmiproReviewResourceModel;
use Magento\Framework\Model\AbstractModel;

class Importexportproductreviews extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(EmiproReviewResourceModel::class);
    }
}

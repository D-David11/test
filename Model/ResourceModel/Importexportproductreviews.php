<?php
/**
 * Copyright © Emipro Technologies Pvt Ltd. All rights reserved.
 * @license http://shop.emiprotechnologies.com/license-agreement/
 */

namespace Emipro\Importexportproductreviews\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Importexportproductreviews extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('emipro_importexportproductreviews', 'id');
    }
}

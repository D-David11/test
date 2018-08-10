<?php
/**
 * Copyright © Emipro Technologies Pvt Ltd. All rights reserved.
 * @license http://shop.emiprotechnologies.com/license-agreement/
 */

namespace Emipro\Importexportproductreviews\Model\ResourceModel\Importexportproductreviews;

use Emipro\Importexportproductreviews\Model\Importexportproductreviews as EmiproreviewsModel;
use Emipro\Importexportproductreviews\Model\ResourceModel\Importexportproductreviews as ReviewsResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            EmiproreviewsModel::class,
            ReviewsResourceModel::class
        );
    }
}

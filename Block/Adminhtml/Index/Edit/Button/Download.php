<?php
/**
 * Copyright © Emipro Technologies Pvt Ltd. All rights reserved.
 * @license http://shop.emiprotechnologies.com/license-agreement/
 */

namespace Emipro\Importexportproductreviews\Block\Adminhtml\Index\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Download extends Generic implements ButtonProviderInterface
{
    /**
     * get button data
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Download Sample Data CSV'),
            'on_click' => sprintf("location.href = '%s';", $this->getBackUrl()),
        ];
    }

    /**
     * Get URL for Download button
     *
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/downloadcsv');
    }
}

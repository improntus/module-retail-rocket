<?php

namespace Improntus\RetailRocket\Block\Adminhtml\Button;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Generate
 *
 * @Version 1.0.19
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\RetailRocket\Block\Adminhtml\Button
 */
class Generate extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $element = null;

        /** @var Button $buttonBlock  */
        $buttonBlock = $this->getForm()->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Button');

        $url = $this->getUrl("retailrocket/generator/generate");

        $data = [
            'class'   => 'improntus-retailrocket-generate-feed',
            'label'   => __('Generate Retail Rocket Feed Manually'),
            'onclick' => "setLocation('" . $url . "')",
        ];

        return $buttonBlock->setData($data)->toHtml();
    }
}

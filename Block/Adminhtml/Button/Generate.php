<?php

namespace Improntus\RetailRocket\Block\Adminhtml\Button;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Generate
 *
 * @version 1.0.3
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
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
        
        $html = $buttonBlock->setData($data)->toHtml();
        
        return $html;
    }
}

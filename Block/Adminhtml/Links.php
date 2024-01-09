<?php
namespace Improntus\RetailRocket\Block\Adminhtml;

use Improntus\RetailRocket\Helper\Data;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Links
 *
 * @version 1.0.17
 * @author Improntus <https://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Block\Adminhtml
 */
class Links extends Field
{
    /**
     * @var Data
     */
    protected $_retailRocketHelper;

    /**
     * Links constructor.
     * @param Context $context
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Data $helper
    ) {
        $this->_retailRocketHelper = $helper;
        parent::__construct($context);
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function _getElementHtml(AbstractElement $element)
    {
        $element = null;

        $links = $this->_retailRocketHelper->getRetailRocketFeedLinks();

        $html = '<div>';

        foreach ($links as $_link)
        {
            $html .= "<p><span>{$_link['store_name']}:</span><br />";
            $html .= "<a href='{$_link['link']}' download='{$_link['file']}' target='_blank'>{$_link['link']}</a></p>";
        }

        $html .= '</div>';

        return $html;
    }
}

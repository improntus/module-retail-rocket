<?php

namespace Improntus\RetailRocket\Block\Adminhtml\System\Config\Form;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Stdlib\DateTime;

/**
 * Class Date
 *
 * @Version 1.0.19
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\RetailRocket\Block\Adminhtml\System\Config\Form
 */
class Date extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * Date constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $element->setDateFormat(DateTime::DATE_INTERNAL_FORMAT);
        $element->setTimeFormat('HH:mm:ss');
        $element->setShowsTime(true);
        return parent::render($element);
    }
}

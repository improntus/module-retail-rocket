<?php

namespace Improntus\RetailRocket\Block\Adminhtml\System\Config\Form;

use Magento\Framework\Module\ModuleListInterface;

/**
 * Class Version
 *
 * @version 1.0.13
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Block\Adminhtml\System\Config\Form
 */
class Version extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;

    /**
     * Version constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        $this->_moduleList = $moduleList;

        parent::__construct($context, $data);
    }

    /**
     * Render version field considering request parameter
     *
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->getModuleInfoHtml();
    }

    /**
     * Receive extension information html
     *
     * @return string
     */
    public function getModuleInfoHtml()
    {
        $moduleVersion = $this->getVersion();

        $html = '<tr><td class="label" colspan="4" style="text-align: left;"><div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">
            <a href="https://retailrocket.net/">RetailRocket</a> integration. Version: ' . $moduleVersion . '</div></td></tr>';

        return $html;
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->_moduleList->getOne('Improntus_RetailRocket')['setup_version'];
    }
}

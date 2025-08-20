<?php

namespace Improntus\RetailRocket\Block\Adminhtml\System\Config\Form;

use Magento\Framework\Module\ModuleListInterface;

/**
 * Class Version
 *
 * @version 1.0.21
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
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

        return '<tr>
            <td class="label" colspan="4" style="text-align: left;">
                <div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">
                    <a href="https://retailrocket.net/">RetailRocket</a> integration. <strong>Version</strong>:
                    <a href="https://github.com/improntus/module-retail-rocket/releases">' . $moduleVersion . '</a>
                    <br>
                    <a href="https://github.com/improntus/module-retail-rocket/wiki">'. __('User Manual / Wiki') . '</a>
                    <br>
                    <br>

                    <strong>Developed by</strong> <a href="https://improntus.com/">Improntus</a> - <a href="https://partners.adobe.com/s/directory/solution/improntus#expertise">Adobe Gold Solution Partner</a>
                </div>
                </td>
            </tr>';
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return $this->_moduleList->getOne('Improntus_RetailRocket')['setup_version'];
    }
}

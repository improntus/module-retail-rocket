<?php

namespace Improntus\RetailRocket\Block;

use Improntus\RetailRocket\Helper\Data;
use Magento\Catalog\Block\Category\View;
use Magento\Catalog\Helper\Category as CategoryHelper;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class Category
 *
 * @version 1.0.11
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Block
 */
class Category extends View
{
    /**
     * @var Data
     */
    protected $_retailRocketHelper;

    /**
     * Category constructor.
     * @param Context $context
     * @param Resolver $layerResolver
     * @param Registry $registry
     * @param CategoryHelper $categoryHelper
     * @param Data $helper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Resolver $layerResolver,
        Registry $registry,
        CategoryHelper $categoryHelper,
        Data $helper,
        array $data = []
    )
    {
        $this->_retailRocketHelper = $helper;

        parent::__construct($context, $layerResolver, $registry, $categoryHelper, $data);
    }

    /**
     * @return string
     */
    public function getPixelHtml()
    {
        $html = '';

        if($this->_retailRocketHelper->isModuleEnabled())
        {
            $categoryId = $this->getCurrentCategory()->getId();

            $html = <<<HTML
    <!-- Begin RetailRocket CategoryView Event -->
    <script type="text/javascript">
        (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
		try { rrApi.categoryView('$categoryId'); } catch(e) {}
	})
    </script>
    <!-- End RetailRocket CategoryView Event -->
HTML;
        }

        return $html;
    }
}

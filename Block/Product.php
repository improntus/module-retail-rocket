<?php

namespace Improntus\RetailRocket\Block;

use Improntus\RetailRocket\Helper\Data;
use Improntus\RetailRocket\Model\Session;
use Magento\Framework\View\Element\Template;

/**
 * Class Tracker
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Block
 */
class Product extends Tracker
{
    /**
     * @var Session
     */
    protected $_retailRocketSession;

    /**
     * Product constructor.
     * @param Template\Context $context
     * @param Data $helper
     * @param Session $retailRocketSession
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Data $helper,
        Session $retailRocketSession,
        array $data = []
    )
    {
        $this->_retailRocketSession = $retailRocketSession;

        parent::__construct($context, $helper, $data);
    }

    /**
     * @return string
     */
    public function getPixelHtml()
    {
        $html = '';
        $productInfo = $this->_retailRocketSession->getViewProduct();

        if($this->_helper->isModuleEnabled() && count($productInfo) && isset($productInfo['product_ids']))
        {
            $productIds = implode(',',$productInfo['product_ids']);

            $html = <<<HTML
    <!-- Begin RetailRocket ProductView Event -->
    <script type="text/javascript">
                (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
            try{ rrApi.groupView(['$productIds']); } catch(e) {}
        })
    </script>
    <!-- End RetailRocket ProductView Event -->
HTML;
        }

        return $html;
    }
}
<?php

namespace Improntus\RetailRocket\Block;

use Improntus\RetailRocket\Helper\Data;
use Improntus\RetailRocket\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\View\Element\Template;

/**
 * Class Product
 *
 * @version 1.0.8
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
     * @param Customer $customer
     */
    public function __construct(
        Template\Context $context,
        Data $helper,
        Session $retailRocketSession,
        array $data = [],
        Customer $customer
    )
    {
        $this->_retailRocketSession = $retailRocketSession;

        parent::__construct($context, $helper, $data,$customer);
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

            if($this->_helper->isStockIdEnabled())
            {
                $websiteCodeStockId = $this->_helper->getCurrentStoreCode();

                    $html = <<<HTML
<!-- Begin RetailRocket ProductView StockId Event -->
<script type="text/javascript">
    (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
        try{ rrApi.groupView([$productIds],{stockId: "{$websiteCodeStockId}"}); } catch(e) {}
    })
</script>
<!-- End RetailRocket ProductView Event -->
HTML;
            }
            else
            {
                $html = <<<HTML
<!-- Begin RetailRocket ProductView Event -->
<script type="text/javascript">
    (window["rrApiOnReady"] = window["rrApiOnReady"] || []).push(function() {
        try{ rrApi.groupView([$productIds]); } catch(e) {}
    })
</script>
<!-- End RetailRocket ProductView Event -->
HTML;
            }
        }

        return $html;
    }
}
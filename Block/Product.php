<?php

namespace Improntus\RetailRocket\Block;

use Improntus\RetailRocket\Helper\Data;
use Improntus\RetailRocket\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\View\Element\Template;

/**
 * Class Product
 *
 * @version 1.0.21
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\RetailRocket\Block
 */
class Product extends Tracker
{
    /**
     * @var Session
     */
    protected $_retailRocketSession;

    /**
     * @param Template\Context $context
     * @param Data             $helper
     * @param Session          $retailRocketSession
     * @param Customer         $customer
     * @param array            $data
     */
    public function __construct(
        Template\Context $context,
        Data $helper,
        Session $retailRocketSession,
        Customer $customer,
        array $data = []
    )
    {
        $this->_retailRocketSession = $retailRocketSession;

        parent::__construct($context, $helper,$customer,$data);
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

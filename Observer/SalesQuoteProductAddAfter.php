<?php

namespace Improntus\RetailRocket\Observer;

use Improntus\RetailRocket\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Item;

/**
 * Class SalesQuoteProductAddAfter
 *
 * @Version 1.0.19
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\RetailRocket\Observer
 */
class SalesQuoteProductAddAfter implements ObserverInterface
{
    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @var Data
     */
    protected $_retailRocketHelper;

    /**
     * @var Data
     */
    protected $_retailRocketSession;

    /**
     * SalesQuoteProductAddAfter constructor.
     * @param \Improntus\RetailRocket\Model\Session $retailRocketSession
     * @param Session $checkoutSession
     * @param Data $retailRocketHelper
     */
    public function __construct(
		\Improntus\RetailRocket\Model\Session $retailRocketSession,
		Session $checkoutSession,
		Data $retailRocketHelper
	) {
		$this->_retailRocketSession = $retailRocketSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_retailRocketHelper = $retailRocketHelper;
	}

    /**
	 * @param Observer $observer
	 *
	 * @return void
	 */
	public function execute(Observer $observer)
    {
		if(!$this->_retailRocketHelper->isModuleEnabled())
		{
			return $this;
		}

		$items = $observer->getItems();
        $productId = null;

		/** @var Item $item */
		foreach ($items as $item)
		{
			if ($item->getProductType() == 'configurable')
			{
				continue;
			}

            $productId = $item->getProduct()->getId();
		}

        $this->_retailRocketSession->setStockId(false);

		if($this->_retailRocketHelper->isStockIdEnabled())
		{
            $this->_retailRocketSession->setStockId($this->_retailRocketHelper->getCurrentStoreCode());
        }

		$this->_retailRocketSession->setAddToCart($productId);

		return $this;
	}
}

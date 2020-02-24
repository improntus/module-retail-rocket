<?php

namespace Improntus\RetailRocket\Observer;

use Improntus\RetailRocket\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Class CatalogControllerProductInitAfter
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Observer
 */
class CatalogControllerProductInitAfter implements ObserverInterface
{
    /**
     * @var Data
     */
	protected $_retailRocketHelper;

    /**
     * @var Session
     */
	protected $_checkoutSession;

    /**
     * @var \Improntus\RetailRocket\Model\Session
     */
	protected $_retailRocketSession;

    /**
     * CatalogControllerProductInitAfter constructor.
     * @param \Improntus\RetailRocket\Model\Session $retailRocketSession
     * @param Session $checkoutSession
     * @param Data $helper
     */
	public function __construct(
		\Improntus\RetailRocket\Model\Session $retailRocketSession,
		Session $checkoutSession,
		Data $helper
	) {
		$this->_retailRocketSession = $retailRocketSession;
		$this->_checkoutSession = $checkoutSession;
		$this->_retailRocketHelper = $helper;
	}

    /**
     * @param Observer $observer
     * @return $this|void
     */
	public function execute(Observer $observer)
    {
		/**
         * @var Product $product
         */
		$product = $observer->getProduct();

		if (!$this->_retailRocketHelper->isModuleEnabled() || !$product) {
			return $this;
		}

		$data = [
			'type'        => 'product',
			'product_ids' => [$product->getId()],
		];

		$this->_retailRocketSession->setViewProduct($data);

		return $this;
	}
}
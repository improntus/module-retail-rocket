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
 * @version 1.0.7
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

        $productIds = [];
        $productIds[] = $product->getId();

		if($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
		{
            $simpleProductIds = $product->getTypeInstance()->getUsedProductIds($product);
            $productIds = array_merge($productIds,$simpleProductIds);
        }

        if($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE)
        {
            $childProductIds = $product->getTypeInstance()->getAssociatedProductIds($product);
            $productIds = array_merge($productIds,$childProductIds);
        }

		$data = [
			'type'        => 'product',
			'product_ids' => $productIds,
		];

		$this->_retailRocketSession->setViewProduct($data);

		return $this;
	}
}
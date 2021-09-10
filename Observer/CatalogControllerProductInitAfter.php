<?php

namespace Improntus\RetailRocket\Observer;

use Improntus\RetailRocket\Helper\Data;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable;

/**
 * Class CatalogControllerProductInitAfter
 *
 * @version 1.0.8
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
     * @var \Improntus\RetailRocket\Model\Session
     */
	protected $_retailRocketSession;

    /**
     * @var Configurable
     */
	protected $_configurable;

    /**
     * CatalogControllerProductInitAfter constructor.
     *
     * @param \Improntus\RetailRocket\Model\Session $retailRocketSession
     * @param Data                                  $helper
     * @param Configurable                          $configurable
     */
	public function __construct(
		\Improntus\RetailRocket\Model\Session $retailRocketSession,
		Data $helper,
        Configurable $configurable
	) {
		$this->_retailRocketSession = $retailRocketSession;
		$this->_retailRocketHelper = $helper;
		$this->_configurable = $configurable;
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

		if($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
		{
            //only send simple item ids (1.0.8)
		    //send all simple products from configurable (1.0.9)
            $simpleProducts = $this->_configurable->getChildrenIds($product->getId());
            isset($simpleProducts[0]) ? $productIds = $simpleProducts[0] : [];
        }

        if($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE)
        {
            $productIds = $product->getTypeInstance()->getAssociatedProductIds($product); //only send simple item ids (1.0.8)
        }

		$data = [
			'type'        => 'product',
			'product_ids' => $productIds,
		];

		$this->_retailRocketSession->setViewProduct($data);

		return $this;
	}
}
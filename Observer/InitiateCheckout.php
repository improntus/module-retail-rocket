<?php

namespace Improntus\RetailRocket\Observer;

use Improntus\RetailRocket\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class InitiateCheckout
 *
 * @version 1.0.14
 * @author Improntus <https://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Observer
 */
class InitiateCheckout implements ObserverInterface
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
     * InitiateCheckout constructor.
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
     * @return $this|void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
	public function execute(Observer $observer )
    {
		if (!$this->_retailRocketHelper->isModuleEnabled())
		{
			return $this;
		}

		if (!count($this->_checkoutSession->getQuote()->getAllVisibleItems()))
		{
			return $this;
		}

		$this->_retailRocketSession->setInitiateCheckout();

		return $this;
	}
}

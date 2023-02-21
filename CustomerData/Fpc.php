<?php

namespace Improntus\RetailRocket\CustomerData;

use Improntus\RetailRocket\Helper\Data;
use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;

/**
 * Class Fpc
 *
 * @version 1.0.14
 * @author Improntus <https://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\CustomerData
 */
class Fpc implements SectionSourceInterface
{
    /**
     * @var Data
     */
    protected $_retailRocketHelper;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var Session
     */
    protected $_customerSession;

    /***
     * @var CurrentCustomer
     */
    protected $_currentCustomer;

    /**
     * Fpc constructor.
     * @param UrlInterface $urlBuilder
     * @param Session $customerSession
     * @param Data $helper
     * @param CurrentCustomer $currentCustomer
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Session $customerSession,
        Data $helper,
        CurrentCustomer $currentCustomer
    ) {
        $this->_urlBuilder = $urlBuilder;
        $this->_customerSession = $customerSession;
        $this->_retailRocketHelper = $helper;
        $this->_currentCustomer = $currentCustomer;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getSectionData()
    {
        $data = [
            'events' => []
        ];

        if ($this->_retailRocketHelper->getSession()->hasAddToCart())
        {
            // Get the add-to-cart information since it's unique to the user
            // but might be displayed on a cached page
            $data['events'][] = [
                'eventName' => 'AddToCart',
                'eventAdditional' => [
                    'productId' => $this->_retailRocketHelper->getSession()->getAddToCart(),
                    'stockId' => $this->_retailRocketHelper->isStockIdEnabled() ? $this->_retailRocketHelper->getCurrentStoreCode() : null
                ],
            ];
        }
        return $data;
    }
}

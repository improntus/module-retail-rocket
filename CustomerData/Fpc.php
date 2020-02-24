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
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\CustomerData
 */
class Fpc implements SectionSourceInterface
{
    /**
     * @var Data
     */
    protected $_helper;

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
        $this->_helper = $helper;
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

        if ($this->_helper->getSession()->hasAddToCart())
        {
            // Get the add-to-cart information since it's unique to the user
            // but might be displayed on a cached page
            $data['events'][] = [
                'eventName' => 'AddToCart',
                'eventAdditional' => $this->_helper->getSession()->getAddToCart()
            ];
        }
        return $data;
    }
}
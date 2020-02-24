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
class Newsletter implements SectionSourceInterface
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

        $retailRocketSession = $this->_helper->getSession();

        if ($retailRocketSession->getUserNewsletter())
        {
            $data['events'][] = [
                'eventName' => 'SubscriberNew',
                'eventAdditional' => $retailRocketSession->getUserNewsletter()
            ];
        }
        $retailRocketSession->setUserNewsletter(false);

        return $data;
    }
}
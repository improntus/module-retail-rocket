<?php

namespace Improntus\RetailRocket\Observer;

use Improntus\RetailRocket\Helper\Data;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Customer;

/**
 * Class NewsletterSubscriberSave
 *
 * @version 1.0.17
 * @author Improntus <https://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Observer
 */
class NewsletterSubscriberSave implements ObserverInterface
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
     * @var CustomerRepositoryInterface
     */
	protected $_customerRepository;

    /**
     * @var StoreManagerInterface
     */
	protected $_storeManager;

    /**
     * @var Session
     */
	protected $_customerSession;

    /**
     * NewsletterSubscriberSave constructor.
     * @param \Improntus\RetailRocket\Model\Session $retailRocketSession
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param Session $customerSession
     */
	public function __construct(
		\Improntus\RetailRocket\Model\Session $retailRocketSession,
		Data $helper,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        Session $customerSession
	) {
		$this->_retailRocketSession = $retailRocketSession;
		$this->_retailRocketHelper = $helper;
		$this->_customerRepository = $customerRepository;
		$this->_storeManager = $storeManager;
		$this->_customerSession = $customerSession;
	}

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws NoSuchEntityException
     */
	public function execute(Observer $observer)
    {
        $subscribedEmail = (bool)$observer->getEvent()->getRequest()->getParam('is_subscribed');

        if(!$this->_retailRocketHelper->isModuleEnabled() || !$subscribedEmail)
        {
            return $this;
        }

        $userData = $this->getUserData();

        $data = [
			'type'        => 'user',
			'user_data'   => $userData,
		];

		$this->_retailRocketSession->setUserNewsletter($data);

		return $this;
	}

    /**
     * @return array
     */
	public function getUserData()
    {
        $customer = $this->_customerSession->getCustomer();

        $result = [
            'email' => $customer->getEmail()
        ];

        /**
         * gender (string),
         * age (number, without quotes),
         * name (first name only, string),
         * birthday (string, DD.MM.YYYY format).
         **/
        $result['additional'] = [];
        $result['additional']['name'] = $customer->getFirstname();

        if($customer->getDob())
        {
            $result['additional']['birthday'] = date('d.m.Y',strtotime((string) $customer->getDob()));

            $age = $this->getAge($result['additional']['birthday']);

            if(is_int($age))
                $result['additional']['age'] = $age;
        }

        if($customer->getGender())
        {
            $result['additional']['gender'] = $customer->getAttribute('gender')->getSource()->getOptionText($customer->getData('gender'));
        }

        if($this->_retailRocketHelper->isStockIdEnabled())
        {
            $result['additional']['stockId'] = $this->_retailRocketHelper->getCurrentStoreCode();
        }

        return $result;
    }

    /**
     * @param $birthDate
     * @return false|int|string
     */
    public function getAge($birthDate)
    {
        $birthDate = explode(".", (string) $birthDate);

        return date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[0], $birthDate[2]))) > date("md")
            ? ((date("Y") - $birthDate[2]) - 1)
            : (date("Y") - $birthDate[2]);
    }
}

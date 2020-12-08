<?php

namespace Improntus\RetailRocket\Observer;

use Improntus\RetailRocket\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\Customer;

/**
 * Class CustomerLogin
 *
 * @version 1.0.8
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Observer
 */
class CustomerLogin implements ObserverInterface
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
     * @var Customer
     */
	protected $_customer;

    /**
     * NewsletterSubscriberNew constructor.
     * @param \Improntus\RetailRocket\Model\Session $retailRocketSession
     * @param Data $helper
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param Customer $customer
     */
	public function __construct(
		\Improntus\RetailRocket\Model\Session $retailRocketSession,
		Data $helper,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        Customer $customer
	) {
		$this->_retailRocketSession = $retailRocketSession;
		$this->_retailRocketHelper = $helper;
		$this->_customerRepository = $customerRepository;
		$this->_storeManager = $storeManager;
		$this->_customer = $customer;
	}

    /**
     * @param Observer $observer
     * @return $this|void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
	public function execute(Observer $observer)
    {
        $customer = $observer->getEvent()->getCustomer();

        if (!$this->_retailRocketHelper->isModuleEnabled())
        {
            return $this;
        }

        $userData = $this->getUserData($customer);

        $data = [
			'type'        => 'user',
			'user_data'   => $userData,
		];

		$this->_retailRocketSession->setCustomerLogged($data);

		return $this;
	}

    /**
     * @param $customer
     * @return array
     */
	public function getUserData($customer)
    {
        $result = [
            'email' => $customer->getEmail(),
            'additional' => []
        ];

        $result['additional']['name'] = $customer->getFirstname();

        if($customer->getDob())
        {
            $result['additional']['birthday'] = date('d.m.Y',strtotime($customer->getDob()));

            $age = $this->getAge($result['additional']['birthday']);

            if(is_int($age))
                $result['additional']['age'] = $age;
        }

        if($customer->getGender())
        {
            $result['additional']['gender'] = $this->_customer->getAttribute('gender')->getSource()->getOptionText($customer->getGender());
        }

        return $result;
    }

    /**
     * @param $birthDate
     * @return false|int|string
     */
    public function getAge($birthDate)
    {
        $birthDate = explode(".", $birthDate);

        $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[0], $birthDate[2]))) > date("md")
            ? ((date("Y") - $birthDate[2]) - 1)
            : (date("Y") - $birthDate[2]));

        return $age;
    }
}
<?php

namespace Improntus\RetailRocket\Block;

use Improntus\RetailRocket\Model\Session;
use Magento\Customer\Model\Customer;
use Magento\Framework\View\Element\Template;
use Improntus\RetailRocket\Helper\Data;
use Magento\Sales\Model\Order;

/**
 * Class Tracker
 *
 * @version 1.0.4
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Block
 */
class Tracker extends Template
{
    /**
     * @var Data
     */
	protected $_helper;

    /**
     * @var Customer
     */
    protected $_customer;

    /**
     * Tracker constructor.
     * @param Template\Context $context
     * @param Data $helper
     * @param array $data
     * @param Customer $customer
     */
	public function __construct(
	    Template\Context $context,
        Data $helper,
        array $data = [],
        Customer $customer
    )
    {
        $this->_helper = $helper;
        $this->_customer = $customer;

        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getPartnerId()
    {
        return $this->_helper->getPartnerId();
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->_helper->getOrder();
    }

    /**
     * @return mixed|null
     */
    public function getAddToCart()
    {
        return $this->_helper->getSession()->getAddToCart();
    }

    /**
     * @return string|null
     */
    public function getStockId()
    {
        return $this->_helper->getSession()->getStockId();
    }

    /**
     * @param $value
     * @return Session
     */
    public function setStockId($value)
    {
        return $this->_helper->getSession()->setAddToCart($value);
    }

    /**
     * @param $value
     * @return Session
     */
    public function setAddToCart($value)
    {
        return $this->_helper->getSession()->setAddToCart($value);
    }

    /**
     * @return mixed|null
     */
    public function getCustomerLogged()
    {
        return $this->_helper->getSession()->getCustomerLogged();
    }

    /**
     * @param $dob
     * @return string
     */
    public function getCustomerBirthdate($dob)
    {
        return date('d.m.Y',strtotime($dob));
    }

    /**
     * @param $birthDate
     * @return false|int|string
     */
    public function getCustomerAge($birthDate)
    {
        $birthDate = date('d.m.Y',strtotime($birthDate));
        $birthDate = explode(".", $birthDate);

        $age = (date("md", date("U", mktime(0, 0, 0, $birthDate[1], $birthDate[0], $birthDate[2]))) > date("md")
            ? ((date("Y") - $birthDate[2]) - 1)
            : (date("Y") - $birthDate[2]));

        return $age;
    }

    /**
     * @param $gender
     * @return string
     */
    public function getCustomerGender($gender)
    {
        return $this->_customer->getAttribute('gender')->getSource()->getOptionText($gender);
    }

    /**
     * @return string
     */
    public function getPrivacyPoliciesUrl()
    {
        return $this->_helper->getPrivacyPoliciesUrl();
    }

    /**
     * @return string
     */
    public function getAlwaysSubscribeCustomerEmail()
    {
        return $this->_helper->getAlwaysSubscribeCustomerEmail();
    }
}
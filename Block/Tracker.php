<?php

namespace Improntus\RetailRocket\Block;

use Magento\Framework\View\Element\Template;
use Improntus\RetailRocket\Helper\Data;

/**
 * Class Tracker
 * @package Improntus\RetailRocket\Block
 */
class Tracker extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Improntus\RetailRocket\Helper\Data
     */
	protected $_helper;

    /**
     * Tracker constructor.
     * @param Template\Context $context
     * @param Data $helper
     * @param array $data
     */
	public function __construct(
	    Template\Context $context,
        Data $helper,
        array $data = []
    )
    {
        $this->_helper = $helper;

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
     * @return \Magento\Sales\Model\Order
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
     * @param $value
     * @return \Improntus\RetailRocket\Model\Session
     */
    public function setAddToCart($value)
    {
        return $this->_helper->getSession()->setAddToCart($value);
    }
}
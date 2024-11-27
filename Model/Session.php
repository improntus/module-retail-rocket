<?php

namespace Improntus\RetailRocket\Model;

use Magento\Framework\Session\SessionManager;

/**
 * Class Session
 *
 * @Version 1.0.18
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Solution Partner
 * @copyright Copyright (c) 2024 Improntus
 * @package Improntus\RetailRocket\Model
 */
class Session extends SessionManager
{
    /**
     * @var array
     */
    protected $_ephemeralData = [];

    /**
     * @param $data
     * @return $this
     */
    public function setAddToCart($data)
    {
        $this->setData('add_to_cart', $data);
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getAddToCart()
    {
        if ($this->hasAddToCart()) {
            $data = $this->getData('add_to_cart');
            $this->unsetData('add_to_cart');
            return $data;
        }
        return null;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setStockId($data)
    {
        $this->setData('stock_id', $data);
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getStockId()
    {
        if ($this->hasAddToCart()) {
            $data = $this->getData('stock_id');
            $this->unsetData('stock_id');
            return $data;
        }
        return null;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setCustomerLogged($data)
    {
        $this->setData('customer_logged_in', $data);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCustomerLogged()
    {
        $data = $this->getData('customer_logged_in');
        $this->unsetData('customer_logged_in');

        return $data;
    }

    /**
     * @return bool
     */
    public function hasAddToCart()
    {
        return $this->hasData('add_to_cart');
    }

    /**
     * @return bool
     */
    public function hasAddToWishlist()
    {
        return $this->hasData('add_to_wishlist');
    }

    /**
     * @return bool
     */
    public function hasInitiateCheckout()
    {
        $has = $this->hasData('initiate_checkout');
        if ($has) {
            $this->unsetData('initiate_checkout');
        }
        return $has;
    }

    /**
     * @return $this
     */
    public function setInitiateCheckout()
    {
        $this->setData('initiate_checkout', true);
        return $this;
    }

    /**
     * @return bool
     */
    public function hasViewProduct()
    {
        return $this->_hasEphemeral('view_product');
    }

    /**
     * @return mixed|null
     */
    public function getViewProduct()
    {
        if ($this->hasViewProduct()) {
            $data = $this->_getEphemeral('view_product');
            $this->_unsetEphemeral('view_product');
            return $data;
        }
        return null;
    }

    /**
     * @param $data
     * @return $this
     */
    public function setViewProduct($data)
    {
        $this->_setEphemeral('view_product', $data);
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    protected function _setEphemeral($key, $value)
    {
        $this->_ephemeralData[$key] = $value;

        return $this;
    }

    /**
     * @param $key
     * @return mixed
     */
    protected function _getEphemeral($key)
    {
        return isset($this->_ephemeralData[$key])
            ? $this->_ephemeralData[$key]
            : null;
    }

    /**
     * @param $key
     * @return bool
     */
    protected function _hasEphemeral($key)
    {
        return isset($this->_ephemeralData[$key]);
    }

    /**
     * @param $key
     * @return $this
     */
    protected function _unsetEphemeral($key)
    {
        unset($this->_ephemeralData[$key]);
        return $this;
    }
}

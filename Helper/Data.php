<?php

namespace Improntus\RetailRocket\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Improntus\RetailRocket\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Data
 *
 * @version 1.0.1
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var Session
     */
    protected $_retailRocketSession;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var Filesystem
     */
    protected $_fileSystem;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param Session $session
     * @param CheckoutSession $checkoutSession
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        Session $session,
        CheckoutSession $checkoutSession,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    )
    {
        $this->_retailRocketSession = $session;
        $this->_checkoutSession = $checkoutSession;
        $this->_fileSystem = $filesystem;
        $this->_storeManager = $storeManager;

        parent::__construct($context);
    }

    /**
     * @return bool
     */
    public function isModuleEnabled()
    {
        return (boolean)$this->scopeConfig->getValue('retailrocket/configuration/enabled', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getPartnerId()
    {
        return $this->scopeConfig->getValue('retailrocket/configuration/partner_id', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getDescriptionAttribute()
    {
        return $this->scopeConfig->getValue('retailrocket/configuration/description_attribute', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getModelAttribute()
    {
        return $this->scopeConfig->getValue('retailrocket/configuration/model_attribute', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getVendorAttribute()
    {
        return $this->scopeConfig->getValue('retailrocket/configuration/vendor_attribute', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getExtraAttributes()
    {
        return $this->scopeConfig->getValue('retailrocket/configuration/extra_attribute', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getPrivacyPoliciesUrl()
    {
        return $this->scopeConfig->getValue('retailrocket/configuration/privacy_policies_url', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return bool
     */
    public function getAlwaysSubscribeCustomerEmail()
    {
        return (boolean)$this->scopeConfig->getValue('retailrocket/configuration/always_subscribe_customer_email', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->_retailRocketSession;
    }

    /**
     * @return array
     */
    public function getRetailRocketFeedLinks()
    {
        $links = [];

        $mediapath = $this->_fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $stores = $this->_storeManager->getStores();

        $mediaFiles = scandir($mediapath);

        if(is_array($mediaFiles))
        {
            $retailRocketFiles = [];

            foreach ($mediaFiles as $mediaFile)
            {
                if(strpos($mediaFile,'retailrocket-feed-') === 0)
                {
                    $storeId = explode('-',$mediaFile);
                    $storeId = isset($storeId[2]) ? explode('.',$storeId[2]) : null;

                    if(is_array($storeId) && isset($storeId[0]))
                    {
                        $retailRocketFiles[$storeId[0]] = [
                            'store_id' => $storeId[0],
                            'file' => $mediaFile,
                            'link' => '',
                            'store_name' => ''
                        ];
                    }
                }
            }

            foreach ($stores as $store)
            {
                $storeId = $store->getId();

                if(isset($retailRocketFiles[$storeId]))
                {
                    $retailRocketFiles[$storeId]['store_name'] = $store->getName();
                    $retailRocketFiles[$storeId]['link'] = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) .
                        $retailRocketFiles[$storeId]['file'];

                    $links[] = $retailRocketFiles[$storeId];
                }
            }
        }

        return $links;
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        if(!$this->_order)
        {
            $this->_order = $this->_checkoutSession->getLastRealOrder();
        }

        return $this->_order;
    }
}
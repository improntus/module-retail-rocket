<?php

namespace Improntus\RetailRocket\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Improntus\RetailRocket\Model\Session;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Data
 *
 * @version 1.0.6
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
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Data constructor.
     * @param Context $context
     * @param Session $session
     * @param CheckoutSession $checkoutSession
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        Session $session,
        CheckoutSession $checkoutSession,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    )
    {
        $this->_retailRocketSession = $session;
        $this->_checkoutSession = $checkoutSession;
        $this->_fileSystem = $filesystem;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;

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
     * @param null $scopeCode
     * @return bool
     */
    public function isSingleXmlFeedEnabled($scopeCode = null)
    {
        return (boolean)$this->scopeConfig->getValue('retailrocket/configuration/enable_single_feed',ScopeInterface::SCOPE_WEBSITES,$scopeCode);
    }

    /**
     * @return bool
     */
    public function isStockIdEnabled()
    {
        return (boolean)$this->scopeConfig->getValue('retailrocket/configuration/stockid/enable');
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
     * @return string|null
     */
    public function getProductCreationStartDate()
    {
        $productCreationStartDate  = $this->scopeConfig->getValue('retailrocket/configuration/product_creation_start_date', ScopeInterface::SCOPE_STORE);

        $isValidDate = (bool)strtotime($productCreationStartDate);

        if($isValidDate)
        {
            return $productCreationStartDate;
        }
        else{
            return null;
        }
    }

    /**
     * @return array
     */
    public function getStockIdCategoriesIds()
    {
        return explode(',',$this->scopeConfig->getValue('retailrocket/configuration/stockid/root_category_ids'));
    }

    /**
     * @return bool
     */
    public function removeSpecialCharsDescription()
    {
        return (boolean)$this->scopeConfig->getValue('retailrocket/configuration/remove_special_chars_description');
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

            if(isset($retailRocketFiles['stockid']))
            {
                $store = reset($stores);

                $retailRocketFiles['stockid']['store_name'] = __('XML file with StockId');
                $retailRocketFiles['stockid']['link'] = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA) .
                    $retailRocketFiles['stockid']['file'];

                $links[] = $retailRocketFiles['stockid'];
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

    /**
     * @return string|null
     */
    public function getCurrentStoreCode()
    {
        try{
            return $this->_storeManager->getStore()->getCode();
        }
        catch (LocalizedException $exception)
        {
            $this->_logger->error($exception);
            return null;
        }
    }

    /**
     * @param $value
     * @return string|string[]|null
     */
    public function cleanString($value)
    {
        $result = '';

        if (empty($value))
        {
            return $result;
        }

        $length = strlen($value);

        for ($i=0; $i < $length; $i++)
        {
            $current = ord($value{$i});
            if (($current == 0x9) ||
                ($current == 0xA) ||
                ($current == 0xD) ||
                (($current >= 0x20) && ($current <= 0xD7FF)) ||
                (($current >= 0xE000) && ($current <= 0xFFFD)) ||
                (($current >= 0x10000) && ($current <= 0x10FFFF)))
            {
                $result .= chr($current);
            }
            else
            {
                $result .= ' ';
            }
        }
        return $result;
    }
}
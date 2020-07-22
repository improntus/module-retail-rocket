<?php

namespace Improntus\RetailRocket\Controller\Addtocart;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Json\Helper\Data;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

/**
 * Class Index
 *
 * @version 1.0.5
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Controller\Index
 */
class Index extends \Magento\Checkout\Controller\Cart
{
    /**
     * @var
     */
    protected $storeId;

    /**
     * @var CollectionFactory
     */
    protected $_productCollection;

    /**
     * Sales quote repository
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * Coupon factory
     *
     * @var CouponFactory
     */
    protected $couponFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Index constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param Validator $formKeyValidator
     * @param Cart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param CollectionFactory $collectionFactory
     * @param CartRepositoryInterface $quoteRepository
     * @param CouponFactory $couponFactory
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        Session $checkoutSession,
        StoreManagerInterface $storeManager,
        Validator $formKeyValidator,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        CollectionFactory $collectionFactory,
        CartRepositoryInterface $quoteRepository,
        CouponFactory $couponFactory
    )
    {
        $this->quoteRepository = $quoteRepository;
        $this->couponFactory = $couponFactory;

        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );

        $this->_productCollection = $collectionFactory;
        $this->storeId = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId();
        $this->_productRepository = $productRepository;
    }

    /**
     * @param $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    protected function getProductBySku($sku)
    {
        return $this->_productRepository->get($sku, false, $this->storeId);
    }

    /**
     * @param $productId
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    protected function getProductById($productId)
    {
        return $this->_productRepository->getById($productId, false, $this->storeId);
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $sku = $this->getRequest()->getParam('sku') ? explode(',',$this->getRequest()->getParam('sku')) : null;
        $qty = $this->getRequest()->getParam('qty') ? explode(',',$this->getRequest()->getParam('qty')) : null;
        $productId = $this->getRequest()->getParam('id') ? explode(',',$this->getRequest()->getParam('id')) : null;
        $ga = $this->getRequest()->getParam('_ga');
        $cartUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();

        if (isset($ga)) {
            $cartUrl = $cartUrl . "?_ga=" . $ga;
        }

        try {
            if (isset($sku))
            {
                if(is_array($sku))
                {
                    if(count($sku) != count($qty))
                    {
                        throw new NotFoundException(__('Number of product sku must be equal to qty'));
                    }

                    foreach ($sku as $index => $_sku)
                    {
                        $product = $this->getProductBySku($_sku);

                        $params = [
                            'qty' => $qty[$index]
                        ];

                        $this->cart->addProduct($product, $params);
                    }
                }
                else{
                    if(is_array($qty))
                    {
                        throw new NotFoundException(__('Qty must be numeric'));
                    }

                    $product = $this->getProductBySku($sku);

                    $params = [
                        'qty' => $qty ? $qty : 1
                    ];

                    $this->cart->addProduct($product, $params);
                }
            }
            elseif (isset($productId))
            {
                if(is_array($productId))
                {
                    if(count($productId) != count($qty))
                    {
                        throw new NotFoundException(__('Number of product ids must be equal to qty'));
                    }

                    foreach ($productId as $index => $_productId)
                    {
                        $product = $this->getProductById($_productId);

                        $params = [
                            'qty' => $qty[$index]
                        ];

                        $this->cart->addProduct($product, $params);
                    }
                }
                else{
                    if(is_array($qty))
                    {
                        throw new NotFoundException(__('Qty must be numeric'));
                    }

                    $product = $this->getProductById($productId);

                    $params = [
                        'qty' => $qty ? $qty : 1
                    ];

                    $this->cart->addProduct($product, $params);
                }
            } else {
                throw new NotFoundException(__('Not found'));
            }

            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            $this->cart->save();

            $couponCode = $this->getRequest()->getParam('coupon');

            if ($couponCode) {
                $cartQuote = $this->quoteRepository->getActive($this->cart->getQuote()->getId());

                $codeLength = strlen($couponCode);

                try {
                    $isCodeLengthValid = $codeLength && $codeLength <= \Magento\Checkout\Helper\Cart::COUPON_CODE_MAX_LENGTH;

                    $itemsCount = $cartQuote->getItemsCount();
                    if ($itemsCount)
                    {
                        $cartQuote->getShippingAddress()->setCollectShippingRates(true);
                        $cartQuote->setCouponCode($isCodeLengthValid ? $couponCode : '')->collectTotals();
                        $this->quoteRepository->save($cartQuote);
                    }

                    if ($codeLength) {
                        $escaper = $this->_objectManager->get('Magento\Framework\Escaper');
                        if (!$itemsCount) {
                            if ($isCodeLengthValid) {
                                $coupon = $this->couponFactory->create();
                                $coupon->load($couponCode, 'code');
                                if ($coupon->getId()) {
                                    $this->_checkoutSession->getQuote()->setCouponCode($couponCode)->save();
                                    $this->messageManager->addSuccess(
                                        __(
                                            'You used coupon code "%1".',
                                            $escaper->escapeHtml($couponCode)
                                        )
                                    );
                                } else {
                                    $this->messageManager->addError(
                                        __(
                                            'The coupon code "%1" is not valid.',
                                            $escaper->escapeHtml($couponCode)
                                        )
                                    );
                                }
                            } else {
                                $this->messageManager->addError(
                                    __(
                                        'The coupon code "%1" is not valid.',
                                        $escaper->escapeHtml($couponCode)
                                    )
                                );
                            }
                        } else {
                            if ($isCodeLengthValid && $couponCode == $cartQuote->getCouponCode()) {
                                $this->messageManager->addSuccess(
                                    __(
                                        'You used coupon code "%1".',
                                        $escaper->escapeHtml($couponCode)
                                    )
                                );
                            } else {
                                $this->messageManager->addError(
                                    __(
                                        'The coupon code "%1" is not valid.',
                                        $escaper->escapeHtml($couponCode)
                                    )
                                );
                            }
                        }
                    } else {
                        $this->messageManager->addSuccess(__('You canceled the coupon code.'));
                    }
                } catch (LocalizedException $e) {
                    $this->messageManager->addError($e->getMessage());
                } catch (Exception $e) {
                    $this->messageManager->addError(__('We cannot apply the coupon code.'));
                    $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                }
            }

            return $this->goBack($cartUrl);

        } catch (LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true))
            {
                $this->messageManager->addNotice(
                    $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError(
                        $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($message)
                    );
                }
            }
            $cartUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();
            if (isset($ga)) {
                $cartUrl = $cartUrl . "?_ga=" . $ga;
            }
            return $this->goBack($cartUrl);

        } catch (Exception $e)
        {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->messageManager->addException($e, $e->getMessage());
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);

            $cartUrl = $this->_objectManager->get('Magento\Checkout\Helper\Cart')->getCartUrl();
            if (isset($ga)) {
                $cartUrl = $cartUrl . "?_ga=" . $ga;
            }
            return $this->goBack($cartUrl);
        }
    }

    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param Product $product
     * @return $this|Redirect
     */
    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get(Data::class)->jsonEncode($result)
        );
    }
}
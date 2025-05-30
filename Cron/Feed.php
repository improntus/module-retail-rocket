<?php

namespace Improntus\RetailRocket\Cron;

use Exception;
use Improntus\RetailRocket\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableProduct;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\Write;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Swatches\Helper\Data as SwatchesHelperData;
use Psr\Log\LoggerInterface;
use Magento\InventorySales\Model\GetProductSalableQty;
use Magento\InventorySales\Model\StockByWebsiteIdResolver;

/**
 * Class Feed
 *
 * @Version 1.0.19
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\RetailRocket\Cron
 */
class Feed
{
    /**
     * @var Data
     */
    protected $_retailRocketHelper;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CategoryFactory
     */
    protected $_categoryFactory;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var TimezoneInterface
     */
    protected $_timeZone;

    /**
     * @var File
     */
    protected $_driverFile;

    /**
     * File handle
     *
     * @var null|Write
     */
    protected $_fileWrite = null;

    /**
     * @var array
     */
    private $_products = [];

    /**
     * @var array
     */
    protected $_categories = [];

    /**
     * @var string
     */
    protected $_descriptionAttribute;

    /**
     * @var array
     */
    protected $_modelAttribute = [];

    /**
     * @var array
     */
    protected $_vendorAttribute = [];

    /**
     * @var array
     */
    protected $_extraAttributes = [];

    /**
     * @var AttributeCollection
     */
    protected $_attributeCollection;

    /**
     * @var ConfigurableProduct
     */
    protected $_configurable;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var SwatchesHelperData
     */
    protected $_helperSwatches;

    /**
     * @var GetProductSalableQty
     */
    protected $getProductSalableQty;

    /**
     * @var StockByWebsiteIdResolver
     */
    protected $stockByWebsiteIdResolver;

    /**
     * Feed constructor.
     *
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param CategoryFactory $categoryFactory
     * @param ProductFactory $productFactory
     * @param TimezoneInterface $timezone
     * @param File $driverFile
     * @param AttributeCollection $attributeCollection
     * @param ConfigurableProduct $configurable
     * @param ProductRepositoryInterface $productRepository
     * @param SwatchesHelperData $helperSwatches
     */
    public function __construct(
        Data $helper,
        LoggerInterface $logger,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        CategoryFactory $categoryFactory,
        ProductFactory $productFactory,
        TimezoneInterface $timezone,
        File $driverFile,
        AttributeCollection $attributeCollection,
        ConfigurableProduct $configurable,
        ProductRepositoryInterface $productRepository,
        SwatchesHelperData $helperSwatches,
        GetProductSalableQty $getProductSalableQty,
        StockByWebsiteIdResolver $stockByWebsiteIdResolver
    ) {
        $this->_retailRocketHelper = $helper;
        $this->logger = $logger;
        $this->_filesystem = $filesystem;
        $this->_storeManager = $storeManager;
        $this->_categoryFactory = $categoryFactory;
        $this->_productFactory = $productFactory;
        $this->_timeZone = $timezone;
        $this->_driverFile = $driverFile;
        $this->_descriptionAttribute = $helper->getDescriptionAttribute() ? $helper->getDescriptionAttribute() : 'description';
        $modelAttribute = $helper->getModelAttribute();
        $vendorAttribute = $helper->getVendorAttribute();
        $this->_attributeCollection = $attributeCollection;
        $this->_configurable = $configurable;
        $this->_productRepository = $productRepository;
        $this->_helperSwatches = $helperSwatches;
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockByWebsiteIdResolver = $stockByWebsiteIdResolver;

        $extraAttributes = $helper->getExtraAttributes();

        $eavAttributeCollection = $this->_attributeCollection->getData();

        if ($extraAttributes) {
            $attributeCodes = explode(',', $extraAttributes);

            foreach ($attributeCodes as $attributeCode) {
                $type = null;

                foreach ($eavAttributeCollection as $_attribute) {
                    if ($_attribute['attribute_code'] == $attributeCode) {
                        $type = $_attribute['frontend_input'];
                    }
                }

                $this->_extraAttributes[$attributeCode] = $type;
            }
        }

        if ($vendorAttribute) {
            $this->_vendorAttribute = [];

            foreach ($eavAttributeCollection as $_attribute) {
                if ($_attribute['attribute_code'] == $vendorAttribute) {
                    $this->_vendorAttribute[$vendorAttribute] = $_attribute['frontend_input'];
                }
            }
        }

        if ($modelAttribute) {
            $this->_modelAttribute = [];

            foreach ($eavAttributeCollection as $_attribute) {
                if ($modelAttribute == $this->_modelAttribute) {
                    $this->_modelAttribute[$vendorAttribute] = $_attribute['frontend_input'];
                }
            }
        }
    }

    public function execute()
    {
        try {
            $this->generateByStore();

            if ($this->_retailRocketHelper->isStockIdEnabled()) {
                $this->generateWithStockId();
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        }
    }

    /**
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function generateByStore()
    {
        $stores = $this->_storeManager->getStores();

        foreach ($stores as $_store) {
            if ($_store->getConfig('retailrocket/configuration/enable_single_feed')) {
                $this->_categories = $this->getCategoryTree($_store->getRootCategoryId(), $_store->getId());
                $this->_products = $this->getProducts($_store->getWebsiteId(), $_store->getId(), true);

                $this->saveToFile($_store->getId());
            }
        }
    }

    /**
     * @throws FileSystemException
     * @throws NoSuchEntityException
     */
    public function generateWithStockId()
    {
        $stores = $this->_storeManager->getWebsites();
        $categoryIds = $this->_retailRocketHelper->getStockIdCategoriesIds();

        foreach ($categoryIds as $categoryId) {
            $this->_categories[] = $this->getCategoryTree($categoryId);
        }

        $productsByStore = [];

        $allProducts = $this->getProductCollection(null, true);

        foreach ($stores as $_store) {
            $productsByStore[$_store->getCode()] = [];
            $productsByStore[$_store->getCode()]['products'] = $this->getProductCollection($_store->getWebsiteId(), true, $_store->getId());
        }

        $productByIds = [];
        foreach ($productsByStore as $storeCode => $products) {
            foreach ($products['products'] as $product) {
                $productByIds[$product->getId()][$storeCode] = $product;
            }
        }

        $this->_products = $this->getProductsWithStockId($allProducts, $productByIds);
        $this->saveToFile('stockid', true);
    }

    /**
     * @param $rootCategoryId
     * @param null $storeId
     * @return array
     */
    public function getCategoryTree($rootCategoryId, $storeId = null)
    {
        $result = [];

        $excludedCategories = $this->_retailRocketHelper->getExcludedCategories();

        $categoryTree = $this->getStoreCategories($rootCategoryId);
        foreach ($categoryTree as $category) {
            if (!is_null($excludedCategories) && in_array($category->getId(),$excludedCategories)) {
                continue;
            }

            $result[] = [
                'id' => $category->getId(),
                'name' => $this->_retailRocketHelper->replaceXmlEntities($category->getName()),
                'parentId' => $category->getParentId() == $rootCategoryId ? null : $category->getParentId()
            ];
        }
        return $result;
    }

    /**
     * @param $rootCategoryId
     * @return \Magento\Catalog\Model\ResourceModel\Category\Collection|Collection
     */
    public function getStoreCategories($rootCategoryId)
    {
        $category = $this->_categoryFactory->create();

        $recursionLevel = max(
            0,
            99
        );

        return $category->getCategories($rootCategoryId, $recursionLevel, true, true);
    }

    /**
     * @param null $websiteId
     * @param bool $allAttributes
     * @param null $storeId
     * @return AbstractCollection
     */
    public function getProductCollection($websiteId = null, $allAttributes = false, $storeId = null)
    {
        $productModel = $this->_productFactory->create();
        $collection = $productModel->getCollection();

        if ($allAttributes) {
            $collection->addAttributeToSelect('*')
                ->addUrlRewrite();
        } else {
            $collection->addAttributeToSelect('name')
                ->addAttributeToSelect('special_from_date')
                ->addAttributeToSelect('special_to_date')
                ->addAttributeToSelect('price')
                ->addAttributeToSelect('special_price')
                ->addAttributeToSelect('description')
                ->addAttributeToSelect('short_description')
                ->addAttributeToSelect('visibility')
                ->addAttributeToSelect('image')
                ->addAttributeToSelect('small_image')
                ->addUrlRewrite();

            $collection->addAttributeToSelect($this->_descriptionAttribute);

            if (count($this->_modelAttribute)) {
                $collection->addAttributeToSelect(key($this->_modelAttribute));
            }

            if (count($this->_vendorAttribute)) {
                $collection->addAttributeToSelect(key($this->_vendorAttribute));
            }

            if (count($this->_extraAttributes)) {
                foreach ($this->_extraAttributes as $extraAttribute => $type) {
                    $collection->addAttributeToSelect($extraAttribute);
                }
            }
        }

        if ($storeId) {
            $collection->setStoreId($storeId);
            $collection->addStoreFilter($storeId); //1.0.11
        }

        if ($websiteId) {
            $collection->addWebsiteFilter([$websiteId]);
        }

        $productCreationStartDate = $this->_retailRocketHelper->getProductCreationStartDate();

        if ($productCreationStartDate) {
            $collection->addAttributeToFilter('created_at', ['gteq' => $productCreationStartDate]);
        }

        return $collection;
    }

    /**
     * @param $websiteId
     * @param $storeId
     * @param false $allAttributes
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProducts($websiteId, $storeId, $allAttributes = false)
    {
        $result = [];

        $collection = $this->getProductCollection($websiteId, $allAttributes, $storeId);
        $i = 0;
        $notVisibleProductsParents = [];

        $stockIdByWebsite = $this->stockByWebsiteIdResolver->execute($websiteId)->getStockId();

        foreach ($collection as $product) {
            if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE) {
                $parentProduct = $this->_configurable->getParentIdsByChild($product->getId());
                if (count($parentProduct)) {
                    $parent = array_pop($parentProduct);
                    if (!isset($notVisibleProductsParents[$parent])) {
                        $notVisibleProductsParents[$parent] = [];
                    }
                    $notVisibleProductsParents[$parent][] = $product->getId();
                }
                continue;
            }

            if ($product->getTypeId() == Grouped::TYPE_CODE) {
                $params = $this->getProductParams($product);

                $lastCategoryId = $this->getProductLastCategoryId(
                    $product->getCategoryIds()
                );

                $groupedPrice = $this->_retailRocketHelper->getGroupedPrice($product);

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $this->formatUrl($this->_retailRocketHelper->replaceXmlEntities($product->getProductUrl())),
                    'price' => (float)$groupedPrice,
                    'picture' => $this->_retailRocketHelper->replaceXmlEntities($this->_retailRocketHelper->getProductImageUrl($product)),
                    'name' => $this->_retailRocketHelper->replaceXmlEntities($product->getName()),
                    'description' => $product->getData($this->_descriptionAttribute),
                    'available' => $product->getIsSalable(),
                    'categories' => $lastCategoryId,
                    'group_id' => null,
                    'params' => $params,
                    'visibility' => $product->getVisibility(),
                    'model' => $this->getRetailRocketAttributeValue($product, $this->_modelAttribute),
                    'vendor' => $this->getRetailRocketAttributeValue($product, $this->_vendorAttribute),
                ];

                $groupId = $product->getId();

                $childProducts = $product->getTypeInstance()->getAssociatedProducts($product);
                foreach ($childProducts as $childProduct) {
                    $i++;

                    $params = $this->getProductParams($childProduct);

                    $lastCategoryId = $this->getProductLastCategoryId(
                        $this->getAvailableCategoryIds($childProduct->getCategoryIds())
                    );

                    $result[$i] = [
                        'id' => $childProduct->getId(),
                        'url' => $this->formatUrl($childProduct->getProductUrl()),
                        'price' => (float)$childProduct->getPrice(),
                        'picture' => $this->_retailRocketHelper->getProductImageUrl($childProduct),
                        'name' => $this->_retailRocketHelper->replaceXmlEntities($childProduct->getName()),
                        'description' => $childProduct->getData($this->_descriptionAttribute),
                        'available' => $this->getAvailableStatus($childProduct,$stockIdByWebsite),
                        'categories' => $lastCategoryId,
                        'group_id' => $groupId,
                        'params' => $params,
                        'visibility' => $childProduct->getVisibility(),
                        'model' => $this->getRetailRocketAttributeValue($childProduct, $this->_modelAttribute),
                        'vendor' => $this->getRetailRocketAttributeValue($childProduct, $this->_vendorAttribute),
                    ];
                }
                $i++;
                continue;
            }

            $price = (float)$product->getData('price');
            $finalPrice = (float)$product->getData('final_price');
            $specialPrice = $product->getData('special_price');
            $specialFromDate = $product->getData('special_from_date');
            $specialToDate = $product->getData('special_to_date');
            $minimalPrice = (float)$product->getMinimalPrice();

            $groupId = null;

            if ($finalPrice == 0) {
                $now = $this->_timeZone->date()->format('Y-m-d H:i:s');
                $finalPrice = $price;

                if ((!is_null($specialPrice) || $specialPrice != 0) && $specialPrice < $price && $specialFromDate <= $now && $now <= $specialToDate) {
                    $finalPrice = (float)$specialPrice;
                }
            }

            $productImage = $this->_retailRocketHelper->getProductImageUrl($product);

            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $groupId = $product->getId();

                /** RETOQUE PARA INFORMAR EL PRECIO ESPECIAL Y NORMAL DE UN CONFIGURABLE */
                $configurabLePrice = $this->_retailRocketHelper->getConfigurablePrice($product);

                $configurableAttributes = $product->getTypeInstance()->getConfigurableOptions($product);
                $options = [];

                foreach ($configurableAttributes as $attr) {
                    foreach ($attr as $p) {
                        $options[$p['sku']][$p['attribute_code']] = $p['option_title'];
                    }
                }

                $params = $this->getProductParams($product);

                $lastCategoryId = $this->getProductLastCategoryId(
                    $this->getAvailableCategoryIds($product->getCategoryIds())
                );

                $configurableUrl = $product->getProductUrl();
                $configurableName = $product->getName();

                //Configurable item
                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $this->formatUrl($configurableUrl),
                    'price' => (float)$configurabLePrice['min'],
                    'picture' => $productImage,
                    'name' => $configurableName,
                    'description' => $product->getData($this->_descriptionAttribute),
                    'available' => $product->getIsSalable(),
                    'categories' => $lastCategoryId,
                    'group_id' => $product->getId(),
                    'params' => $params,
                    'visibility' => $product->getVisibility(),
                    'model' => $this->getRetailRocketAttributeValue($product, $this->_modelAttribute),
                    'vendor' => $this->getRetailRocketAttributeValue($product, $this->_vendorAttribute),
                ];

                /** Check if applies special price for configurable product */
                $applySpecial = $configurabLePrice['min'] < $configurabLePrice['max'];

                if ($applySpecial) {
                    $result[$i]['price'] = (float)$configurabLePrice['min'];
                    $result[$i]['oldprice'] = $configurabLePrice['max'];
                }

                if (!is_null($minimalPrice) && $minimalPrice != 0 && $minimalPrice < $price) {
                    $result[$i]['price'] = $minimalPrice;
                    $result[$i]['oldprice'] = $price;
                }

                $simpleProducts = $this->getSimpleProductsFromConfigurable($product, $notVisibleProductsParents);
                foreach ($simpleProducts as $simpleProduct) {
                    $i++;

                    $params = $this->getProductParams($simpleProduct);

                    if (isset($options[$simpleProduct->getSku()])) {
                        foreach ($options[$simpleProduct->getSku()] as $code => $label) {
                            $params[] = [
                                'code' => $code,
                                'label' => $label
                            ];

                            /** Si el parametro es el colo, le mando el codigo hexa que le corresponde al circulo */
                            if ($code == 'color') {
                                $swatchData = $this->_helperSwatches->getSwatchesByOptionsId([$simpleProduct->getColor()]);
                                if (count($swatchData)) {
                                    $swatchData = reset($swatchData);

                                    $params[] = [
                                        'code' => 'color_swatch',
                                        'label' => $swatchData['value']
                                    ];
                                }
                            }
                            /** Fin del agregado al original */
                        }
                    }

                    //PONER UNA LOGICA Y OPCION POR SI SE QUIERE USAR LA CATEGORIA DEL CONFIGURABLE PADRE Y NO LOS SIMPLES
//                    $categoryIds = $this->getAvailableCategoryIds($simpleProduct->getCategoryIds());
//                    $lastCategoryId = (is_array($categoryIds) && count($categoryIds)) ? end($categoryIds) : null;

                    if ($simpleProduct->getTypeId() == Type::TYPE_SIMPLE) {
                        $productAvailable = $this->getAvailableStatus($simpleProduct,$stockIdByWebsite);
                    } else {
                        $productAvailable = $simpleProduct->getIsSalable();
                    }

                    $price = (float)$simpleProduct->getPrice();
                    $finalPrice = (float)$simpleProduct->getFinalPrice();

                    //Simple item
                    $result[$i] = [
                        'id' => $simpleProduct->getId(),
                        'url' => $this->formatUrl($configurableUrl), // 1.0.9 use configurable url for simple products
                        'price' => $finalPrice,
                        'picture' => $this->_retailRocketHelper->getProductImageUrl($simpleProduct),
                        'name' => $this->_retailRocketHelper->useParentNameSimple() ? $configurableName : $simpleProduct->getName(), // 1.0.9
                        'description' => $product->getData($this->_descriptionAttribute),
                        'available' => $productAvailable,
                        'categories' => $lastCategoryId,
                        'group_id' => $groupId,
                        'params' => $params,
                        'visibility' => $simpleProduct->getVisibility(),
                        'model' => $this->getRetailRocketAttributeValue($simpleProduct, $this->_modelAttribute),
                        'vendor' => $this->getRetailRocketAttributeValue($simpleProduct, $this->_vendorAttribute),
                    ];

                    $applySpecial = $this->_retailRocketHelper->applySpecialPrice(
                        $price,
                        $simpleProduct->getSpecialPrice(),
                        $simpleProduct->getSpecialFromDate(),
                        $simpleProduct->getSpecialToDate()
                    );

                    if ($applySpecial) {
                        $result[$i]['price'] = (float)$specialPrice;
                        $result[$i]['oldprice'] = $price;
                    }

                    if (!is_null($finalPrice) && $finalPrice < $price) {
                        $result[$i]['price'] = (float)$finalPrice;
                        $result[$i]['oldprice'] = $price;
                    }
                }
                $i++;
                continue;
            } else {

                $params = $this->getProductParams($product);

                $lastCategoryId = $this->getProductLastCategoryId(
                    $this->getAvailableCategoryIds($product->getCategoryIds())
                );

                if ($product->getTypeId() == Type::TYPE_BUNDLE) {
                    $productAvailable = $product->getIsSalable();
                } else {
                    $productAvailable = $this->getAvailableStatus($product,$stockIdByWebsite);
                }

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $this->formatUrl($product->getProductUrl()),
                    'price' => $finalPrice,
                    'picture' => $productImage,
                    'name' => $product->getName(),
                    'description' => $product->getData($this->_descriptionAttribute),
                    'available' => $productAvailable,
                    'categories' => $lastCategoryId,
                    'group_id' => $groupId,
                    'params' => $params,
                    'visibility' => $product->getVisibility(),
                    'model' => $this->getRetailRocketAttributeValue($product, $this->_modelAttribute),
                    'vendor' => $this->getRetailRocketAttributeValue($product, $this->_vendorAttribute),
                ];

                $applySpecial = $this->_retailRocketHelper->applySpecialPrice(
                    $price,
                    $specialPrice,
                    $specialFromDate,
                    $specialToDate
                );

                if ($applySpecial) {
                    $result[$i]['price'] = (float)$specialPrice;
                    $result[$i]['oldprice'] = $price;
                }

                if (!is_null($minimalPrice) && $minimalPrice != 0 && $minimalPrice < $price) {
                    $result[$i]['price'] = $minimalPrice;
                    $result[$i]['oldprice'] = $price;
                }
            }
            $i++;
        }
        unset($collection);
        unset($productModel);
        return $result;
    }

    /**
     * @param $product
     * @param $attributes
     * @return string|null
     */
    protected function getRetailRocketAttributeValue($product, $attributes)
    {
        if (count($attributes)) {
            $key = key($attributes);
            return $this->_retailRocketHelper->getAttributeValue($product, $key, $attributes[$key]);
        }
        return null;
    }

    /**
     * @param $product
     * @return array
     */
    protected function getProductParams($product)
    {
        $params = [];
        if (count($this->_extraAttributes)) {
            foreach ($this->_extraAttributes as $extraAttribute => $type) {
                $params[] = [
                    'code' => $extraAttribute,
                    'label' => $this->_retailRocketHelper->getAttributeValue($product, $extraAttribute, $type)
                ];
            }
        }
        return $params;
    }

    /**
     * @param $categoryIds
     * @param bool $checkRetailRocketQty
     * @return array|null
     */
    protected function getProductLastCategoryId($categoryIds, $checkRetailRocketQty = false)
    {
        $lastCategoryId = null;
        if (is_array($categoryIds)) {
            $retailRocketQty = $checkRetailRocketQty ? $this->_retailRocketHelper->getQtyCategoriesToSend() : 0;
            $categoryIdsQty = count($categoryIds);

            if ($categoryIdsQty == 1 && (!$checkRetailRocketQty || ($retailRocketQty == 1))) {
                $lastCategoryId = [end($categoryIds)];
            }
            if ($categoryIdsQty >= 2 && (!$checkRetailRocketQty || ($retailRocketQty == 2))) {
                $lastCategoryId = [$categoryIds[$categoryIdsQty - 1], $categoryIds[$categoryIdsQty - 2]];
            }
        }
        return $lastCategoryId;
    }

    /**
     * @param string $url
     * @return string
     * @throws NoSuchEntityException
     */
    protected function formatUrl($url)
    {
        $paramUrl = $this->_retailRocketHelper->addStoreParamToProductUrl();

        if (!$paramUrl)
        {
            if ($temp = strstr($url, '?', true))
            {
                $url = $temp;
            }

            return $url;
        }

        $storeCode = $this->_storeManager->getStore()
            ? $this->_storeManager->getStore()->getData('code')
            : '';

        return strlen((string) $storeCode)
            ? $url . '?___store=' . $storeCode
            : $url;
    }

    /**
     * @param $product
     * @param $notVisibleProductsParents
     * @return mixed
     */
    protected function getSimpleProductsFromConfigurable($product, $notVisibleProductsParents)
    {
        $simpleProducts = $product->getTypeInstance()->getUsedProducts($product);

        /** FIX: in some cases $simpleProducts is not returning all its child products (@version 1.0.8) */
        if (isset($notVisibleProductsParents[$product->getId()]) && count($simpleProducts) != count($notVisibleProductsParents[$product->getId()])) {
            $simpleIdsFromObject = [];
            foreach ($simpleProducts as $simpleProduct) {
                $simpleIdsFromObject[] = $simpleProduct->getId();
            }
            $diff = array_diff($notVisibleProductsParents[$product->getId()], $simpleIdsFromObject);
            if (count($diff)) {
                foreach ($diff as $_productId) {
                    try {
                        $productToAdd = $this->_productRepository->getById($_productId);
                        $simpleProducts[] = $productToAdd;
                    } catch (Exception $e) {
                        $this->logger->error($e->getMessage());
                    }
                }
            }
        }
        return $simpleProducts;
    }

    /**
     * OS-97: Devuelve el valor de available para un producto simple asociado a un configurable,
     * o un producto de tipo NO configurable.
     * @param $product
     * @return bool
     */
    protected function getAvailableStatus($product,$stockId)
    {
        if ($product->getStatus() != Status::STATUS_ENABLED) {
            return false;
        }

        $salableQty = $this->getProductSalableQty->execute($product->getSku(),$stockId);

        return $salableQty > 0;
    }

    /**
     * @param $allProducts
     * @param $productByIds
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductsWithStockId($allProducts, $productByIds)
    {
        $result = [];

        $collection = $allProducts;

        $i = 0;
        $notVisibleProductsParents = [];

        foreach ($collection as $product) {
            if ($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE && $product->getTypeId() == Type::TYPE_SIMPLE) {
                $parentProduct = $this->_configurable->getParentIdsByChild($product->getId());
                if (count($parentProduct)) {
                    $parent = array_pop($parentProduct);

                    if (!isset($notVisibleProductsParents[$parent])) {
                        $notVisibleProductsParents[$parent] = [];
                    }

                    $notVisibleProductsParents[$parent][] = $product->getId();
                }
                continue;
            }

            if ($product->getTypeId() == Grouped::TYPE_CODE) {
                $params = $this->getProductParams($product);

                $lastCategoryId = $this->getProductLastCategoryId(
                    $product->getCategoryIds(),
                    true
                );

                $groupedPrice = $this->_retailRocketHelper->getGroupedPrice($product);

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $this->formatUrl($product->getUrlInStore()),
                    'price' => (float)$groupedPrice,
                    'picture' => $this->_retailRocketHelper->getProductImageUrl($product),
                    'name' => $product->getName(),
                    'description' => $product->getData($this->_descriptionAttribute),
                    'available' => false,
                    'categories' => $lastCategoryId,
                    'group_id' => null,
                    'params' => $params,
                    'visibility' => $product->getVisibility(),
                    'model' => $this->getRetailRocketAttributeValue($product, $this->_modelAttribute),
                    'vendor' => $this->getRetailRocketAttributeValue($product, $this->_vendorAttribute),
                ];

                $groupId = $product->getId();

                $childProducts = $product->getTypeInstance()->getAssociatedProducts($product);

                if (isset($result[$i]['id']) && isset($productByIds[$result[$i]['id']])) {
                    $result[$i]['stock'] = [];

                    foreach ($productByIds[$result[$i]['id']] as $webisteCode => $productByStockId) {
                        $result[$i]['stock'][$webisteCode] = [];

                        $result[$i]['stock'][$webisteCode]['id'] = $productByStockId->getId();
                        $result[$i]['stock'][$webisteCode]['name'] = $product->getName();
                        $result[$i]['stock'][$webisteCode]['description'] = $productByStockId->getData($this->_descriptionAttribute);

                        $productAvailable = $productByStockId->getIsSalable();
                        $groupedPriceByStockId = $this->_retailRocketHelper->getGroupedPrice($productByStockId);

                        $result[$i]['stock'][$webisteCode]['available'] = $productAvailable;
                        $result[$i]['stock'][$webisteCode]['price'] = (float)$groupedPriceByStockId; //@TODO
                        $result[$i]['stock'][$webisteCode]['url'] = $this->formatUrl($productByStockId->getProductUrl());
                        $result[$i]['stock'][$webisteCode]['picture'] = $this->_retailRocketHelper->getProductImageUrl($productByStockId);
                    }
                }

                foreach ($childProducts as $childProduct) {
                    $i++;

                    $params = $this->getProductParams($childProduct);

                    $lastCategoryId = $this->getProductLastCategoryId(
                        $childProduct->getCategoryIds(),
                        true
                    );

                    $result[$i] = [
                        'id' => $childProduct->getId(),
                        'url' => $this->formatUrl($childProduct->getUrlInStore()),
                        'price' => (float)$childProduct->getPrice(), //@TODO
                        'picture' => $this->_retailRocketHelper->getProductImageUrl($childProduct),
                        'name' => $childProduct->getName(),
                        'description' => $childProduct->getData($this->_descriptionAttribute),
                        'available' => false,
                        'categories' => $lastCategoryId,
                        'group_id' => $groupId,
                        'params' => $params,
                        'visibility' => $childProduct->getVisibility(),
                        'model' => $this->getRetailRocketAttributeValue($childProduct, $this->_modelAttribute),
                        'vendor' => $this->getRetailRocketAttributeValue($childProduct, $this->_vendorAttribute),
                    ];

                    if (isset($result[$i]['id']) && isset($productByIds[$result[$i]['id']])) {
                        $result[$i]['stock'] = [];

                        foreach ($productByIds[$result[$i]['id']] as $webisteCode => $productByStockId) {
                            $result[$i]['stock'][$webisteCode] = [];

                            $result[$i]['stock'][$webisteCode]['id'] = $productByStockId->getId();
                            $result[$i]['stock'][$webisteCode]['name'] = $childProduct->getName();
                            $result[$i]['stock'][$webisteCode]['description'] = $productByStockId->getData($this->_descriptionAttribute);

                            if ($productByStockId->getTypeId() == Type::TYPE_SIMPLE) {
                                $productAvailable = $this->getAvailableStatus($productByStockId,$stockIdByWebsite);
                            } else {
                                $productAvailable = $productByStockId->getIsSalable();
                            }

                            $result[$i]['stock'][$webisteCode]['available'] = $productAvailable;

                            $price = (float)$childProduct->getData('price');
                            $finalPrice = (float)$childProduct->getData('final_price');
                            $specialPrice = $childProduct->getData('special_price');
                            $specialFromDate = $childProduct->getData('special_from_date');
                            $specialToDate = $childProduct->getData('special_to_date');
                            $minimalPrice = (float)$childProduct->getMinimalPrice();

                            $applySpecial = $this->_retailRocketHelper->applySpecialPrice(
                                $price,
                                $specialPrice,
                                $specialFromDate,
                                $specialToDate
                            );

                            $result[$i]['stock'][$webisteCode]['price'] = $finalPrice;

                            if ($applySpecial) {
                                $result[$i]['stock'][$webisteCode]['price'] = (float)$specialPrice;
                                $result[$i]['stock'][$webisteCode]['oldprice'] = $price;
                            }

                            if (!is_null($minimalPrice) && $minimalPrice != 0 && $minimalPrice < $price) {
                                $result[$i]['stock'][$webisteCode]['price'] = $minimalPrice;
                                $result[$i]['stock'][$webisteCode]['oldprice'] = $price;
                            }

                            $result[$i]['stock'][$webisteCode]['url'] = $this->formatUrl($productByStockId->getProductUrl());
                            $result[$i]['stock'][$webisteCode]['picture'] = $this->_retailRocketHelper->getProductImageUrl($productByStockId);
                        }
                    }
                }

                if (isset($result[$i]['id']) && isset($productByIds[$result[$i]['id']])) {
                    $result[$i]['stock'] = [];

                    foreach ($productByIds[$result[$i]['id']] as $webisteCode => $productByStockId) {
                        $result[$i]['stock'][$webisteCode] = [];

                        $result[$i]['stock'][$webisteCode]['id'] = $productByStockId->getId();
                        $result[$i]['stock'][$webisteCode]['name'] = $product->getName();
                        $result[$i]['stock'][$webisteCode]['description'] = $productByStockId->getData($this->_descriptionAttribute);

                        if ($productByStockId->getTypeId() == Type::TYPE_SIMPLE) {
                            $productAvailable = $this->getAvailableStatus($productByStockId,$stockIdByWebsite);
                        } else {
                            $productAvailable = $productByStockId->getIsSalable();
                        }

                        $result[$i]['stock'][$webisteCode]['available'] = $productAvailable;

//                        $finalPrice = (float)$product->getPriceInfo()->getPrice('final_price')->getValue();
                        $specialPrice = $product->getData('special_price');
                        $specialFromDate = $product->getData('special_from_date');
                        $specialToDate = $product->getData('special_to_date');
                        $minimalPrice = (float)$product->getMinimalPrice();

                        $price = $this->_retailRocketHelper->getGroupedPrice($product);

                        $applySpecial = $this->_retailRocketHelper->applySpecialPrice(
                            $price,
                            $specialPrice,
                            $specialFromDate,
                            $specialToDate
                        );

                        $result[$i]['stock'][$webisteCode]['price'] = $price;

                        if ($applySpecial) {
                            $result[$i]['stock'][$webisteCode]['price'] = (float)$specialPrice;
                            $result[$i]['stock'][$webisteCode]['oldprice'] = $price;
                        }

                        if (!is_null($minimalPrice) && $minimalPrice != 0 && $minimalPrice < $price) {
                            $result[$i]['stock'][$webisteCode]['price'] = $minimalPrice;
                            $result[$i]['stock'][$webisteCode]['oldprice'] = $price;
                        }

                        $result[$i]['stock'][$webisteCode]['url'] = $this->formatUrl($productByStockId->getProductUrl());
                        $result[$i]['stock'][$webisteCode]['picture'] = $this->_retailRocketHelper->getProductImageUrl($productByStockId);
                    }
                }
                $i++;
                continue;
            }

            $price = (float)$product->getData('price');
            $finalPrice = (float)$product->getPriceInfo()->getPrice('final_price')->getValue();
            $specialPrice = $product->getData('special_price');
            $specialFromDate = $product->getData('special_from_date');
            $specialToDate = $product->getData('special_to_date');
            $minimalPrice = (float)$product->getMinimalPrice();

            $groupId = null;

            if ($finalPrice == 0 && $product->getTypeId() == Configurable::TYPE_CODE) {
                $finalPrice = $minimalPrice;
            }

            $productImage = $this->_retailRocketHelper->getProductImageUrl($product);

            if ($product->getTypeId() == Configurable::TYPE_CODE) {
                $groupId = $product->getId();

                $configurableAttributes = $product->getTypeInstance()->getConfigurableOptions($product);
                $options = [];

                foreach ($configurableAttributes as $attr) {
                    foreach ($attr as $p) {
                        $options[$p['sku']][$p['attribute_code']] = $p['option_title'];
                    }
                }

                $params = $this->getProductParams($product);

                $lastCategoryId = $this->getProductLastCategoryId(
                    $product->getCategoryIds(),
                    true
                );

                $configurableUrl = $product->getUrlInStore();

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $this->formatUrl($configurableUrl),
                    'price' => (float)$finalPrice,
                    'picture' => $productImage,
                    'name' => $product->getName(),
                    'description' => $product->getData($this->_descriptionAttribute),
                    'available' => false,
                    'categories' => $lastCategoryId,
                    'group_id' => null,
                    'params' => $params,
                    'visibility' => $product->getVisibility(),
                    'hide_from_feed' => true,
                    'model' => $this->getRetailRocketAttributeValue($product, $this->_modelAttribute),
                    'vendor' => $this->getRetailRocketAttributeValue($product, $this->_vendorAttribute),
                ];

                /** Check if applies special price for configurable product */
                $applySpecial = $this->_retailRocketHelper->applySpecialPrice(
                    $price,
                    $specialPrice,
                    $specialFromDate,
                    $specialToDate
                );

                if ($applySpecial) {
                    $result[$i]['price'] = $specialPrice;
                    $result[$i]['oldprice'] = $price;
                }

                if (!is_null($minimalPrice) && $minimalPrice != 0 && $minimalPrice < $price) {
                    $result[$i]['price'] = $minimalPrice;
                    $result[$i]['oldprice'] = $price;
                }

                $configurableProductStore = [];

                if (isset($result[$i]['id']) && isset($productByIds[$result[$i]['id']])) {
                    $result[$i]['stock'] = [];

                    foreach ($productByIds[$result[$i]['id']] as $webisteCode => $productByStockId) {
                        $storesWebsite = $productByStockId->getWebsiteStoreIds();

                        foreach ($storesWebsite as $_storeId) {
                            $store = $this->_storeManager->getStore($_storeId);
                            $storeCode = $store->getCode();
                            $productId = $productByStockId->getId();

                            $result[$i]['stock'][$storeCode] = [];
                            $result[$i]['stock'][$storeCode]['id'] = $productId;
                            $result[$i]['stock'][$storeCode]['name'] = $productByStockId->getResource()->getAttributeRawValue($productId, 'name', $_storeId);
                            $result[$i]['stock'][$storeCode]['description'] = $productByStockId->getResource()->getAttributeRawValue($productId, $this->_descriptionAttribute, $_storeId);

                            $productAvailable = $productByStockId->setStore($store)->getIsSalable();
                            $result[$i]['stock'][$storeCode]['available'] = $productAvailable;

                            $result[$i]['stock'][$storeCode]['price'] = 0;
                            $result[$i]['stock'][$storeCode]['url'] = $this->formatUrl($productByStockId->setStoreId($_storeId)->getProductUrl());
                            $result[$i]['stock'][$storeCode]['picture'] = $this->_retailRocketHelper->getProductImageUrl($productByStockId);

                            $configurableProductStore[$storeCode] = [];
                            $configurableProductStore[$storeCode]['name'] = $result[$i]['stock'][$storeCode]['name'];
                            $configurableProductStore[$storeCode]['description'] = $result[$i]['stock'][$storeCode]['description'];
                            $configurableProductStore[$storeCode]['available'] = $result[$i]['stock'][$storeCode]['available'];
                            $configurableProductStore[$storeCode]['url'] = $this->formatUrl($result[$i]['stock'][$storeCode]['url']);
                            $configurableProductStore[$storeCode]['picture'] = $result[$i]['stock'][$storeCode]['picture'];
                        }
                    }
                }

                $simpleProducts = $this->getSimpleProductsFromConfigurable($product, $notVisibleProductsParents);

                // Hijos del configurable
                foreach ($simpleProducts as $simpleProduct) {
                    $i++;

                    $params = $this->getProductParams($simpleProduct);

                    if (isset($options[$simpleProduct->getSku()])) {
                        foreach ($options[$simpleProduct->getSku()] as $code => $label) {
                            $params[] = [
                                'code' => $code,
                                'label' => $label
                            ];
                        }
                    }

                    $lastCategoryId = $this->getProductLastCategoryId(
                        $simpleProduct->getCategoryIds(),
                        true
                    );

                    $price = (float)$simpleProduct->getPrice();
                    $finalPrice = (float)$simpleProduct->getFinalPrice();

                    $result[$i] = [
                        'id' => $simpleProduct->getId(),
                        'url' => $this->formatUrl($configurableUrl), // Los productos simples llevan la url del configurable 03-05-2021
                        'price' => $finalPrice,
                        'picture' => $this->_retailRocketHelper->getProductImageUrl($simpleProduct),
                        'name' => $simpleProduct->getName(),
                        'description' => $product->getData($this->_descriptionAttribute),
                        'available' => false,
                        'categories' => $lastCategoryId,
                        'group_id' => $groupId,
                        'params' => $params,
                        'visibility' => $simpleProduct->getVisibility(),
                        'model' => $this->getRetailRocketAttributeValue($simpleProduct, $this->_modelAttribute),
                        'vendor' => $this->getRetailRocketAttributeValue($simpleProduct, $this->_vendorAttribute),
                    ];

                    $applySpecial = $this->_retailRocketHelper->applySpecialPrice(
                        $price,
                        $simpleProduct->getSpecialPrice(),
                        $simpleProduct->getSpecialFromDate(),
                        $simpleProduct->getSpecialToDate()
                    );

                    if ($applySpecial) {
                        $result[$i]['price'] = $specialPrice;
                        $result[$i]['oldprice'] = $price;
                    }

                    if (!is_null($finalPrice) && $finalPrice != 0 && $finalPrice < $price) {
                        $result[$i]['price'] = $finalPrice;
                        $result[$i]['oldprice'] = $price;
                    }

                    /** Busco los stores por cada simple */
                    if (isset($productByIds[$result[$i]['id']])) {
                        $result[$i]['stock'] = [];

                        foreach ($productByIds[$result[$i]['id']] as $webisteCode => $productByStockId) {
                            $storesWebsite = $productByStockId->getWebsiteStoreIds();

                            foreach ($storesWebsite as $_store) {
                                $store = $this->_storeManager->getStore($_store);
                                $storeCode = $store->getCode();
                                $productId = $productByStockId->getId();
                                $websiteId = $store->getWebsiteId();

                                $result[$i]['stock'][$storeCode] = [];
                                $result[$i]['stock'][$storeCode]['id'] = $productId;
                                $result[$i]['stock'][$storeCode]['name'] = $productByStockId->getResource()->getAttributeRawValue($productId, 'name', $_store); //$productByStockId->getName();
                                $result[$i]['stock'][$storeCode]['description'] = $productByStockId->getResource()->getAttributeRawValue($productId, $this->_descriptionAttribute, $_store); //$productByStockId->getData($this->_descriptionAttribute);

                                /** Simple and configurable product must be available */
                                $productAvailable = $productByStockId->getIsSalable() && $configurableProductStore[$storeCode]['available'];

                                $result[$i]['stock'][$storeCode]['available'] = $productAvailable;

                                $price = (float)$productByStockId->getPrice();
                                $finalPrice = $this->_retailRocketHelper->getFinalPriceByStore($productId, $websiteId);

                                $result[$i]['stock'][$storeCode]['price'] = $finalPrice;

                                if ($finalPrice < $price) {
                                    $result[$i]['stock'][$storeCode]['price'] = (float)$finalPrice;
                                    $result[$i]['stock'][$storeCode]['oldprice'] = $price;
                                }

                                $_url = $configurableProductStore[$storeCode]['url'] ?? $productByStockId->getProductUrl();

                                $result[$i]['stock'][$storeCode]['url'] = $this->formatUrl($_url);
                                $result[$i]['stock'][$storeCode]['picture'] = $this->_retailRocketHelper->getProductImageUrl($productByStockId);
                            }
                        }
                    }
                }
                continue;
            } else {
                //Configurable
                $params = $this->getProductParams($product);

                $lastCategoryId = $this->getProductLastCategoryId(
                    $simpleProduct->getCategoryIds(),
                    true
                );

                if ($finalPrice == 0) {
                    $finalPrice = $price;
                }

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $this->formatUrl($product->getUrlInStore()),
                    'price' => $finalPrice,
                    'picture' => $productImage,
                    'name' => $product->getName(),
                    'description' => $product->getData($this->_descriptionAttribute),
                    'available' => false,
                    'categories' => $lastCategoryId,
                    'group_id' => null,
                    'params' => $params,
                    'visibility' => $product->getVisibility(),
                    'model' => $this->getRetailRocketAttributeValue($product, $this->_modelAttribute),
                    'vendor' => $this->getRetailRocketAttributeValue($product, $this->_vendorAttribute),
                ];

                $applySpecial = $this->_retailRocketHelper->applySpecialPrice(
                    $price,
                    $specialPrice,
                    $specialFromDate,
                    $specialToDate
                );

                if ($applySpecial) {
                    $result[$i]['price'] = (float)$specialPrice;
                    $result[$i]['oldprice'] = $price;
                }

                if (!is_null($minimalPrice) && $minimalPrice != 0 && $minimalPrice < $price) {
                    $result[$i]['price'] = $minimalPrice;
                    $result[$i]['oldprice'] = $price;
                }
            }

            if (isset($result[$i]['id']) && isset($productByIds[$result[$i]['id']])) {
                $result[$i]['stock'] = [];

                foreach ($productByIds[$result[$i]['id']] as $webisteCode => $productByStockId) {
                    $storesWebsite = $productByStockId->getWebsiteStoreIds();

                    foreach ($storesWebsite as $_storeId) {
                        $store = $this->_storeManager->getStore($_storeId);
                        $storeCode = $store->getCode();
                        $productId = $productByStockId->getId();
                        $websiteId = $store->getWebsiteId();

                        $result[$i]['stock'][$storeCode] = [];
                        $result[$i]['stock'][$storeCode]['id'] = $productId;
                        $result[$i]['stock'][$storeCode]['name'] = $productByStockId->getResource()->getAttributeRawValue($productId, 'name', $_storeId);
                        $result[$i]['stock'][$storeCode]['description'] = $productByStockId->getResource()->getAttributeRawValue($productId, $this->_descriptionAttribute, $_storeId);

                        if ($productByStockId->getTypeId() == Type::TYPE_SIMPLE) {
                            $productAvailable = $this->getAvailableStatus($productByStockId,$stockIdByWebsite);
                        } else {
                            $productAvailable = $productByStockId->getIsSalable();
                        }

                        $result[$i]['stock'][$storeCode]['available'] = $productAvailable;

                        $price = (float)$productByStockId->getPrice();
                        $finalPrice = $this->_retailRocketHelper->getFinalPriceByStore($productId, $websiteId);

                        $result[$i]['stock'][$storeCode]['price'] = $finalPrice;

                        if ($finalPrice < $price) {
                            $result[$i]['stock'][$storeCode]['price'] = (float)$specialPrice;
                            $result[$i]['stock'][$storeCode]['oldprice'] = $price;
                        }

                        $result[$i]['stock'][$storeCode]['url'] = $this->formatUrl($productByStockId->setStoreId($_storeId)->getProductUrl());
                        $result[$i]['stock'][$storeCode]['picture'] = $this->_retailRocketHelper->getProductImageUrl($productByStockId);
                    }
                }
            }
            $i++;
        }
        unset($collection);
        unset($productModel);
        return $result;
    }

    /**
     * @param $productCategoryIds
     * @return array
     */
    public function getAvailableCategoryIds($productCategoryIds)
    {
        $categories = [];

        if (is_array($productCategoryIds) && count($this->_categories)) {
            foreach ($productCategoryIds as $productCategoryId) {
                foreach ($this->_categories as $category) {
                    if ($productCategoryId == $category['id']) {
                        $categories[] = $productCategoryId;
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * @param null $storeId
     * @param bool $stockIdMode
     * @throws FileSystemException
     */
    public function saveToFile($storeId = null, $stockIdMode = false)
    {
        $fileName = "retailrocket-feed-$storeId.xml";

        $dirWrite = $this->_filesystem->getDirectoryWrite(
            DirectoryList::MEDIA
        );

        $fileXml = $dirWrite->getAbsolutePath() . $fileName;

        if (file_exists($fileXml)) {
            $this->_driverFile->deleteFile($fileXml);
        }

        $this->_fileWrite = $dirWrite->openFile($fileName, 'w');

        try {
            $this->_fileWrite->lock();
            try {
                $this->_fileWrite->write($this->buildHeader() . "\n");
                $this->_fileWrite->write($this->buildCategories($stockIdMode) . "\n");
                $this->_fileWrite->write($this->buildProducts() . "\n");
                $this->_fileWrite->write($this->buildFooter() . "\n");
            } finally {
                $this->_fileWrite->unlock();
            }
        } finally {
            $this->_fileWrite->close();
        }
    }

    /**
     * @return string
     */
    public function buildHeader()
    {
        $now = $this->_timeZone->date()->format('Y-m-d H:i');

        $header  = '<?xml version="1.0" encoding="UTF-8"?>';
        $header .= "\n";
        $header .= "<yml_catalog date=\"$now\">";

        return $header . "\n<shop>";
    }

    /**
     * @param bool $stockIdMode
     * @return string
     */
    public function buildCategories(bool $stockIdMode = false): string
    {
        $categories = "<categories>\n";

        if ($stockIdMode) {
            foreach ($this->_categories as $category) {
                foreach ($category as $item) {
                    $categories .= "<category id=\"{$item['id']}\"";

                    if (!is_null($item['parentId'])) {
                        $categories .= " parentId=\"{$item['parentId']}\"";
                    }

                    $categories .= ">";
                    $categories .= $item['name'];
                    $categories .= "</category>\n";
                }
            }
        } else {
            foreach ($this->_categories as $category) {
                $categories .= "<category id=\"{$category['id']}\"";

                if (!is_null($category['parentId'])) {
                    $categories .= " parentId=\"{$category['parentId']}\"";
                }

                $categories .= ">";
                $categories .= $category['name'];
                $categories .= "</category>\n";
            }
        }

        return $categories . "</categories>";
    }

    /**
     * @return string
     */
    public function buildProducts(): string
    {
        $products = "<offers>\n";

        foreach ($this->_products as $product) {
            if (isset($product['hide_from_feed'])) {
                continue;
            }

            $products .= "<offer id=\"{$product['id']}\"";

            if ($product['group_id']) {
                $products .= " group_id=\"{$product['group_id']}\"";
            }

            if ($product['available']) {
                $products .= " available=\"true\"";
            } else {
                $products .= " available=\"false\"";
            }

            $products .= ">";
            $products .= "<url>{$this->_retailRocketHelper->replaceXmlEntities($product['url'])}</url>";
            $products .= "<price>{$product['price']}</price>";

            if (isset($product['oldprice'])) {
                $products .= "<oldprice>{$product['oldprice']}</oldprice>";
            }

            if (!is_null($product['categories'])) {
                foreach ($product['categories'] as $_cat) {
                    $products .= "<categoryId>{$_cat}</categoryId>";
                }
            }

            $products .= "<picture>{$product['picture']}</picture>";

            $product['name'] = $this->_retailRocketHelper->replaceXmlEntities($product['name']);
            $products .= "<name>{$product['name']}</name>";


            if (isset($product['params']) && count($product['params'])) {
                foreach ($product['params'] as $param) {
                    if ($param['label']) {
                        $products .= "<param name=\"{$param['code']}\">{$this->_retailRocketHelper->replaceXmlEntities($param['label'])}</param>";
                    }
                }
            }

            $product['description'] = $product['description'] ? strip_tags((string) $product['description']) : $product['description'];

            /** Prueba temporal para verificar que no se rompa el xml con las descripciones largas que traen html filtrado */
            $product['description'] = $product['description'] ? preg_replace('~[\r\n\t]+~', '', $product['description']) : $product['description'];

            if ($this->_retailRocketHelper->removeSpecialCharsDescription()) {
                $product['description'] = $this->_retailRocketHelper->cleanString($product['description']);
                $product['name'] = $this->_retailRocketHelper->cleanString($product['name']);
            }

            if (!is_null($product['description']) && strlen((string) $product['description']) >= 200) {
                $product['description'] = substr((string) $product['description'], 0, 190);
                $product['description'] = $this->_retailRocketHelper->replaceXmlEntities($product['description']);

                //1.0.10 max length added
                $descriptionMaxLength = $this->_retailRocketHelper->getDescriptionAttributeMaxLength();
                if ($descriptionMaxLength > 0 && !is_null($product['description']) && strlen($product['description']) > $descriptionMaxLength) {
                    $product['description'] = substr($product['description'], 0, $descriptionMaxLength);
                }

                //1.0.9
                $product['description'] = is_null($product['description'])
                    ? $product['description']
                    : substr_replace($product['description'], '...', -3, 3);
            }

            $product['description'] = $this->_retailRocketHelper->replaceXmlEntities($product['description']);

            if ($this->_retailRocketHelper->hasHtml($product['description'])) {
                $products .= "<description><![CDATA[{$product['description']}]]></description>";
            } else {
                $products .= "<description>{$product['description']}</description>";
            }

            if ($product['model']) {
                if ($this->_retailRocketHelper->removeSpecialCharsDescription()) {
                    $product['model'] = $this->_retailRocketHelper->cleanString($product['model']);
                }

                $products .= "<model>{$this->_retailRocketHelper->replaceXmlEntities($product['model'])}</model>";
            }

            if ($product['vendor']) {
                if ($this->_retailRocketHelper->removeSpecialCharsDescription()) {
                    $product['vendor'] = $this->_retailRocketHelper->cleanString($product['vendor']);
                }

                $products .= "<vendor>{$this->_retailRocketHelper->replaceXmlEntities($product['vendor'])}</vendor>";
            }

            if (isset($product['visibility']) && !empty($product['visibility'])) {
                $products .= "<param name=\"visibility\">{$this->_retailRocketHelper->getVisibilityText($product['visibility'])}</param>";
            }

            if (isset($product['stock']) && is_array($product['stock'])) {
                foreach ($product['stock'] as $code => $website) {
                    if ($this->_retailRocketHelper->removeSpecialCharsDescription()) {
                        $website['name'] = $this->_retailRocketHelper->cleanString($website['name']);
                    }

                    $website['name'] = $this->_retailRocketHelper->replaceXmlEntities($website['name']);

                    $products .= "<stock id=\"{$code}\">";
                    $products .= "<picture>{$website['picture']}</picture>";
                    $products .= "<name>{$website['name']}</name>";
                    $products .= "<price>{$website['price']}</price>";
                    $products .= "<url>{$this->_retailRocketHelper->replaceXmlEntities($website['url'])}</url>";

                    $website['available'] = $website['available'] ? 'true' : 'false';

                    $products .= "<available>{$website['available']}</available>";

                    if (isset($website['oldprice'])) {
                        $products .= "<oldprice>{$website['oldprice']}</oldprice>";
                    }

                    if ($this->_retailRocketHelper->removeSpecialCharsDescription()) {
                        $website['description'] = $this->_retailRocketHelper->cleanString($website['description']);
                    }

                    if (!is_null($website['description']) && strlen((string) $website['description']) >= 200) {
                        $productDescription = substr((string) $product['description'], 0, 190);
                        $website['description'] = $this->_retailRocketHelper->replaceXmlEntities($productDescription);
                    }

                    if ($this->_retailRocketHelper->hasHtml($website['description'])) {
                        $website['description'] = $website['description'] ? strip_tags((string) $website['description']) : $website['description'];

                        $products .= "<description><![CDATA[{$website['description']}]]></description>";
                    } else {
                        $products .= "<description>{$website['description']}</description>";
                    }

                    if (isset($website['params']) && count($website['params'])) {
                        foreach ($website['params'] as $param) {
                            if ($param['label']) {
                                $products .= "<param name=\"{$param['code']}\">{$param['label']}</param>";
                            }
                        }
                    }

                    if (isset($website['model'])) {
                        if ($this->_retailRocketHelper->removeSpecialCharsDescription()) {
                            $product['model'] = $this->_retailRocketHelper->cleanString($product['model']);
                        }

                        $products .= "<model>{$this->_retailRocketHelper->replaceXmlEntities($website['model'])}</model>";
                    }

                    if (isset($website['vendor'])) {
                        if ($this->_retailRocketHelper->removeSpecialCharsDescription()) {
                            $product['vendor'] = $this->_retailRocketHelper->cleanString($product['vendor']);
                        }

                        $products .= "<vendor>{$this->_retailRocketHelper->replaceXmlEntities($website['vendor'])}</vendor>";
                    }

                    $products .= "</stock>";
                }
            }

            $products .= "</offer>\n";
        }

        return $products . "</offers>";
    }

    public function buildFooter()
    {
        $footer = "</shop>\n";

        return $footer . "</yml_catalog>";
    }
}

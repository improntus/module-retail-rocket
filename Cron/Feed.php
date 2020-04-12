<?php

namespace Improntus\RetailRocket\Cron;

use Exception;
use Improntus\RetailRocket\Helper\Data;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\Write;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableProduct;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Class Feed
 *
 * @version 1.0.1
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
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
    private $_categories = [];

    /**
     * @var Repository
     */
    protected $_viewAssetRepo;

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
     * @var StockRegistryInterface
     */
    protected $_stockRegistry;

    /**
     * @var ConfigurableProduct
     */
    protected $_configurable;

    /**
     * @var ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * Feed constructor.
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param CategoryFactory $categoryFactory
     * @param ProductFactory $productFactory
     * @param TimezoneInterface $timezone
     * @param File $driverFile
     * @param Repository $viewAssetRepo
     * @param AttributeCollection $attributeCollection
     * @param StockRegistryInterface $stockRegistry
     * @param ConfigurableProduct $configurable
     * @param ProductRepositoryInterface $productRepository
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
        Repository $viewAssetRepo,
        AttributeCollection $attributeCollection,
        StockRegistryInterface $stockRegistry,
        ConfigurableProduct $configurable,
        ProductRepositoryInterface $productRepository
    ) {
        $this->_retailRocketHelper = $helper;
        $this->logger = $logger;
        $this->_filesystem = $filesystem;
        $this->_storeManager = $storeManager;
        $this->_categoryFactory = $categoryFactory;
        $this->_productFactory = $productFactory;
        $this->_timeZone = $timezone;
        $this->_driverFile = $driverFile;
        $this->_viewAssetRepo = $viewAssetRepo;
        $this->_descriptionAttribute = $helper->getDescriptionAttribute() ? $helper->getDescriptionAttribute() : 'description';
        $modelAttribute = $helper->getModelAttribute();
        $vendorAttribute = $helper->getVendorAttribute();
        $this->_attributeCollection = $attributeCollection;
        $this->_stockRegistry = $stockRegistry;
        $this->_configurable = $configurable;
        $this->_productRepository = $productRepository;

        $extraAttributes = $helper->getExtraAttributes();

        $eavAttributeCollection = $this->_attributeCollection->getData();

        if($extraAttributes)
        {
            $attributeCodes = explode(',',$extraAttributes);

            foreach ($attributeCodes as $attributeCode)
            {
                $type = null;

                foreach ($eavAttributeCollection as $_attribute)
                {
                    if($_attribute['attribute_code'] == $attributeCode)
                    {
                        $type = $_attribute['frontend_input'];
                        continue;
                    }
                }

                $this->_extraAttributes[$attributeCode] = $type;
            }
        }

        if($vendorAttribute)
        {
            $this->_vendorAttribute = [];

            foreach ($eavAttributeCollection as $_attribute)
            {
                if($_attribute['attribute_code'] == $vendorAttribute)
                {
                    $this->_vendorAttribute[$vendorAttribute] = $_attribute['frontend_input'];
                    continue;
                }
            }
        }

        if($modelAttribute)
        {
            $this->_modelAttribute = [];

            foreach ($eavAttributeCollection as $_attribute)
            {
                if($modelAttribute == $this->_modelAttribute)
                {
                    $this->_modelAttribute[$vendorAttribute] = $_attribute['frontend_input'];
                    continue;
                }
            }
        }
    }

    public function execute()
    {
        try {
            $this->generate();
        } catch (Exception $e)
        {
            $this->logger->critical($e);
        }
    }

    public function generate()
    {
        $stores = $this->_storeManager->getStores();

        foreach ($stores as $store)
        {
            $this->_categories = $this->getCategoryTree($store->getRootCategoryId());
            $mediaStoreUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            $this->_products = $this->getProducts($store->getId(),$store->getWebsiteId(),$mediaStoreUrl);

            $this->saveToFile($store->getId());
        }
    }

    /**
     * @param $rootCategoryId
     * @return array
     */
    public function getCategoryTree($rootCategoryId)
    {
        $result = [];

        $categoryTree = $this->getStoreCategories($rootCategoryId);

        foreach ($categoryTree as $category)
        {
            $result[] = [
                'id' => $category->getId(),
                'name' => $this->replaceXmlEntities($category->getName()),
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

        $storeCategories = $category->getCategories($rootCategoryId, $recursionLevel, true, true);

        return $storeCategories;
    }

    /**
     * @param int $storeId
     * @param int $websiteId
     * @param string $mediaStoreUrl
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProducts($storeId,$websiteId,$mediaStoreUrl)
    {
        $result = [];

        $productModel = $this->_productFactory->create();
        $collection = $productModel->getCollection()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('special_from_date')
            ->addAttributeToSelect('special_to_date')
            ->addAttributeToSelect('price')
            ->addAttributeToSelect('special_price')
            ->addAttributeToSelect('description')
            ->addAttributeToSelect('short_description')
            ->addAttributeToSelect('visibility')
            ->addAttributeToSelect('image')
            ->addPriceData(null,$websiteId)
            ->addStoreFilter($storeId)
            ->addUrlRewrite();

        $collection->addAttributeToSelect($this->_descriptionAttribute);

        if(count($this->_modelAttribute))
        {
            $collection->addAttributeToSelect(key($this->_modelAttribute));
        }

        if(count($this->_vendorAttribute))
        {
            $collection->addAttributeToSelect(key($this->_vendorAttribute));
        }

        if(count($this->_extraAttributes))
        {
            foreach ($this->_extraAttributes as $extraAttribute => $type)
            {
                $collection->addAttributeToSelect($extraAttribute);
            }
        }

        $i = 0;
        $notVisibleProductsParents = [];

        foreach ($collection as $product)
        {
            if($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE)
            {
                $parentProduct = $this->_configurable->getParentIdsByChild($product->getId());

                if(count($parentProduct))
                {
                    $parent = array_pop($parentProduct);

                    if(!isset($notVisibleProductsParents[$parent]))
                    {
                        $notVisibleProductsParents[$parent] = [];
                    }

                    $notVisibleProductsParents[$parent][] = $product->getId();
                }

                continue;
            }

            if($product->getTypeId() == Grouped::TYPE_CODE)
            {
                $params = [];

                if(count($this->_extraAttributes))
                {
                    foreach ($this->_extraAttributes as $extraAttribute => $type)
                    {
                        $params[] = [
                            'code' => $extraAttribute,
                            'label' => $this->getAttributeValue($product,$extraAttribute,$type)
                        ];
                    }
                }

                $categoryIds = $product->getCategoryIds();
                $lastCategoryId = (is_array($categoryIds) && count($categoryIds)) ? end($categoryIds) : null;

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $product->getProductUrl(),
                    'price' => (float)$product->getMinimalPrice(),
                    'picture' => $this->getProductImageUrl($product->getImage(),$mediaStoreUrl),
                    'name' => $this->replaceXmlEntities($product->getName()),
                    'description' => $this->replaceXmlEntities($product->getData($this->_descriptionAttribute)),
                    'available' => $product->getIsSalable(),
                    'categories' => $lastCategoryId,
                    'group_id' => null,
                    'params' => $params
                ];

                $groupId = $product->getId();

                if(count($this->_modelAttribute))
                {
                    $key = key($this->_modelAttribute);
                    $result[$i]['model'] = $this->getAttributeValue($product,$key,$this->_modelAttribute[$key]);
                }
                else
                {
                    $result[$i]['model'] = null;
                }

                if(count($this->_vendorAttribute))
                {
                    $key = key($this->_vendorAttribute);
                    $result[$i]['vendor'] = $this->getAttributeValue($product,$key,$this->_vendorAttribute[$key]);
                }
                else
                {
                    $result[$i]['vendor'] = null;
                }

                $childProducts = $product->getTypeInstance()->getAssociatedProducts($product);

                foreach ($childProducts as $childProduct)
                {
                    $i++;

                    $params = [];

                    if(count($this->_extraAttributes))
                    {
                        foreach ($this->_extraAttributes as $extraAttribute => $type)
                        {
                            $params[] = [
                                'code' => $extraAttribute,
                                'label' => $this->getAttributeValue($childProduct,$extraAttribute,$type)
                            ];
                        }
                    }

                    $categoryIds = $childProduct->getCategoryIds();
                    $lastCategoryId = (is_array($categoryIds) && count($categoryIds)) ? end($categoryIds) : null;

                    $result[$i] = [
                        'id' => $childProduct->getId(),
                        'url' => $childProduct->getProductUrl(),
                        'price' => (float)$childProduct->getPrice(),
                        'picture' => $this->getProductImageUrl($childProduct->getImage(),$mediaStoreUrl),
                        'name' => $this->replaceXmlEntities($childProduct->getName()),
                        'description' => $this->replaceXmlEntities($childProduct->getData($this->_descriptionAttribute)),
                        'available' => $childProduct->getIsSalable(),
                        'categories' => $lastCategoryId,
                        'group_id' => $groupId,
                        'params' => $params
                    ];

                    if(count($this->_modelAttribute))
                    {
                        $key = key($this->_modelAttribute);
                        $result[$i]['model'] = $this->getAttributeValue($childProduct,$key,$this->_modelAttribute[$key]);
                    }
                    else
                    {
                        $result[$i]['model'] = null;
                    }

                    if(count($this->_vendorAttribute))
                    {
                        $key = key($this->_vendorAttribute);
                        $result[$i]['vendor'] = $this->getAttributeValue($childProduct,$key,$this->_vendorAttribute[$key]);
                    }
                    else
                    {
                        $result[$i]['vendor'] = null;
                    }
                }

                $i++;

                continue;
            }

            $price = (float)$product->getData('price');
            $finalPrice = (float)$product->getData('final_price');
            $specialPrice = $product->getData('special_price');
            $specialFromDate = $product->getData('special_from_date');
            $specialToDate = $product->getData('special_to_date');
            $applySpecial = $this->applySpecialPrice($price,$specialPrice,$specialFromDate,$specialToDate);

            $groupId = null;

            if($finalPrice == 0)
            {
                continue;
            }

            $productImage = $this->getProductImageUrl($product->getImage(),$mediaStoreUrl);

            if($product->getTypeId() == Configurable::TYPE_CODE)
            {
                $groupId = $product->getId();

                $configurableAttributes = $product->getTypeInstance()->getConfigurableOptions($product);
                $options = [];

                foreach($configurableAttributes as $attr)
                {
                    foreach($attr as $p)
                    {
                        $options[$p['sku']][$p['attribute_code']] = $p['option_title'];
                    }
                }

                $params = [];

                if(count($this->_extraAttributes))
                {
                    foreach ($this->_extraAttributes as $extraAttribute => $type)
                    {
                        $params[] = [
                            'code' => $extraAttribute,
                            'label' => $this->getAttributeValue($product,$extraAttribute,$type)
                        ];
                    }
                }

                $categoryIds = $product->getCategoryIds();
                $lastCategoryId = (is_array($categoryIds) && count($categoryIds)) ? end($categoryIds) : null;

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $product->getProductUrl(),
                    'price' => (float)$finalPrice,
                    'picture' => $productImage,
                    'name' => $this->replaceXmlEntities($product->getName()),
                    'description' => $product->getData($this->_descriptionAttribute),
                    'available' => $product->getIsSalable(),
                    'categories' => $lastCategoryId,
                    'group_id' => null,
                    'params' => $params
                ];

                if(count($this->_modelAttribute))
                {
                    $key = key($this->_modelAttribute);
                    $result[$i]['model'] = $this->getAttributeValue($product,$key,$this->_modelAttribute[$key]);
                }
                else
                {
                    $result[$i]['model'] = null;
                }

                if(count($this->_vendorAttribute))
                {
                    $key = key($this->_vendorAttribute);
                    $result[$i]['vendor'] = $this->getAttributeValue($product,$key,$this->_vendorAttribute[$key]);
                }
                else
                {
                    $result[$i]['vendor'] = null;
                }

                $simpleProducts = $product->getTypeInstance()->getUsedProducts($product);

                /** FIX: in some cases $simpleProducts is not returning all its child products (@version 1.0.1) */
                if(isset($notVisibleProductsParents[$product->getId()]))
                {
                    if(count($simpleProducts) != count($notVisibleProductsParents[$product->getId()]))
                    {
                        $simpleIdsFromObject = [];

                        foreach ($simpleProducts as $simpleProduct)
                        {
                            $simpleIdsFromObject[] = $simpleProduct->getId();
                        }

                        $diff = array_diff($notVisibleProductsParents[$product->getId()],$simpleIdsFromObject);

                        if(count($diff))
                        {
                            foreach ($diff as $_productId)
                            {
                                try{
                                    $productToAdd = $this->_productRepository->getById($_productId);
                                    $simpleProducts[] = $productToAdd;
                                }
                                catch (Exception $e){
                                    $this->logger->error($e->getMessage());
                                }
                            }
                        }
                    }
                }

                foreach ($simpleProducts as $simpleProduct)
                {
                    $i++;
                    $params = [];

                    if(isset($options[$simpleProduct->getSku()]))
                    {
                        foreach ($options[$simpleProduct->getSku()] as $code => $label)
                        {
                            $params[] = [
                                'code' => $code,
                                'label' => $label
                            ];
                        }
                    }

                    if(count($this->_extraAttributes))
                    {
                        foreach ($this->_extraAttributes as $extraAttribute => $type)
                        {
                            $params[] = [
                                'code' => $extraAttribute,
                                'label' => $this->getAttributeValue($simpleProduct,$extraAttribute,$type)
                            ];
                        }
                    }

                    $categoryIds = $simpleProduct->getCategoryIds();
                    $lastCategoryId = (is_array($categoryIds) && count($categoryIds)) ? end($categoryIds) : null;

                    if($simpleProduct->getTypeId() == Type::TYPE_SIMPLE)
                    {
                        $stockItem = $this->_stockRegistry->getStockItem(
                            $simpleProduct->getId()
                        );

                        $productAvailable = $simpleProduct->getStatus() == Status::STATUS_ENABLED
                            && $stockItem->getQty() > 0 && $stockItem->getIsInStock();
                    }
                    else{
                        $productAvailable = $simpleProduct->getIsSalable();
                    }

                    $result[$i] = [
                        'id' => $simpleProduct->getId(),
                        'url' => $simpleProduct->getProductUrl(),
                        'price' => (float)$simpleProduct->getFinalPrice(),
                        'picture' => $this->getProductImageUrl($simpleProduct->getImage(),$mediaStoreUrl),
                        'name' => $this->replaceXmlEntities($simpleProduct->getName()),
                        'description' => $product->getData($this->_descriptionAttribute),
                        'available' => $productAvailable,
                        'categories' => $lastCategoryId,
                        'group_id' => $groupId,
                        'params' => $params
                    ];

                    if(count($this->_modelAttribute))
                    {
                        $key = key($this->_modelAttribute);
                        $result[$i]['model'] = $this->getAttributeValue($simpleProduct,$key,$this->_modelAttribute[$key]);
                    }
                    else
                    {
                        $result[$i]['model'] = null;
                    }

                    if(count($this->_vendorAttribute))
                    {
                        $key = key($this->_vendorAttribute);
                        $result[$i]['vendor'] = $this->getAttributeValue($simpleProduct,$key,$this->_vendorAttribute[$key]);
                    }
                    else
                    {
                        $result[$i]['vendor'] = null;
                    }

                    $applySpecial = $this->applySpecialPrice($simpleProduct->getPrice(),$simpleProduct->getSpecialPrice(),
                        $simpleProduct->getSpecialFromDate(),$simpleProduct->getSpecialToDate());

                    if($applySpecial)
                        $result[$i]['oldprice'] = $specialPrice;
                }

                continue;
            }
            else{
                $params = [];

                if(count($this->_extraAttributes))
                {
                    foreach ($this->_extraAttributes as $extraAttribute => $type)
                    {
                        $params[] = [
                            'code' => $extraAttribute,
                            'label' => $this->getAttributeValue($product,$extraAttribute,$type)
                        ];
                    }
                }

                $categoryIds = $product->getCategoryIds();
                $lastCategoryId = (is_array($categoryIds) && count($categoryIds)) ? end($categoryIds) : null;

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $product->getProductUrl(),
                    'price' => $finalPrice,
                    'picture' => $productImage,
                    'name' => $this->replaceXmlEntities($product->getName()),
                    'description' => $product->getData($this->_descriptionAttribute),
                    'available' => $product->getIsSalable(),
                    'categories' => $lastCategoryId,
                    'group_id' => $groupId,
                    'params' => $params
                ];

                if(count($this->_modelAttribute))
                {
                    $key = key($this->_modelAttribute);
                    $result[$i]['model'] = $this->getAttributeValue($product,$key,$this->_modelAttribute[$key]);
                }
                else
                {
                    $result[$i]['model'] = null;
                }

                if(count($this->_vendorAttribute))
                {
                    $key = key($this->_vendorAttribute);
                    $result[$i]['vendor'] = $this->getAttributeValue($product,$key,$this->_vendorAttribute[$key]);
                }
                else
                {
                    $result[$i]['vendor'] = null;
                }

                if($applySpecial)
                    $result[$i]['oldprice'] = $specialPrice;
            }

            $i++;
        }

        unset($collection);
        unset($productModel);

        return $result;
    }

    /**
     * @param $image
     * @param $mediaStoreUrl
     * @return string
     */
    public function getProductImageUrl($image,$mediaStoreUrl)
    {
        if(!$image)
        {
            return $this->_viewAssetRepo->getUrl(
                'Magento_Catalog::images/product/placeholder/image.jpg'
            );
        }
        else
        {
            return $mediaStoreUrl .'catalog/product/'. ltrim($image,'/');
        }
    }

    /**
     * @param $product
     * @param string $attributeCode
     * @param string $attributeType
     * @return string
     */
    public function getAttributeValue($product,$attributeCode,$attributeType)
    {
        return $attributeType == 'select' || $attributeType == 'multiselect' ? $this->replaceXmlEntities($product->getResource()
            ->getAttribute($attributeCode)->getFrontend()->getValue($product)
        ) : $this->replaceXmlEntities($product->getData($attributeCode));
    }

    /**
     * @param $price
     * @param $specialPrice
     * @param $specialFromDate
     * @param $specialToDate
     * @return bool
     */
    public function applySpecialPrice($price,$specialPrice,$specialFromDate,$specialToDate)
    {
        $now = $this->_timeZone->date()->format('Y-m-d H:i:s');

        if(is_null($specialPrice) || !is_null($specialPrice) == 0)
            return false;

        if($specialPrice < $price && $specialFromDate <= $now && $now <= $specialToDate)
        {
            return true;
        }
        else{
            return false;
        }
    }

    /**
     * @param $storeId
     * @throws FileSystemException
     */
    public function saveToFile($storeId)
    {
        $dirWrite = $this->_filesystem->getDirectoryWrite(
            DirectoryList::MEDIA
        );

        $fileXml = $dirWrite->getAbsolutePath()."retailrocket-feed-$storeId.xml";

        if(file_exists($fileXml))
        {
            $this->_driverFile->deleteFile($fileXml);
        }

        $this->_fileWrite = $dirWrite->openFile("retailrocket-feed-$storeId.xml", 'w');

        try {
            $this->_fileWrite->lock();
            try {
                $this->_fileWrite->write($this->buildHeader() . "\n");
                $this->_fileWrite->write($this->buildCategories() . "\n");
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
        $header .= "\n<shop>";

        return $header;
    }

    /**
     * @return string
     */
    public function buildCategories()
    {
        $categories = "<categories>\n";

        foreach ($this->_categories as $category)
        {
            $categories .= "<category id=\"{$category['id']}\"";

            if(!is_null($category['parentId']))
            {
                $categories.= " parentId=\"{$category['parentId']}\"";
            }

            $categories.= ">";
            $categories .= $category['name'];
            $categories .= "</category>\n";
        }

        $categories .= "</categories>";

        return $categories;
    }

    /**
     * @return string
     */
    public function buildProducts()
    {
        $products = "<offers>\n";

        foreach ($this->_products as $product)
        {
            $products .= "<offer id=\"{$product['id']}\"";

            if($product['group_id'])
            {
                $products.= " group_id=\"{$product['group_id']}\"";
            }

            if($product['available'])
            {
                $products.= " available=\"true\"";
            }
            else{
                $products.= " available=\"false\"";
            }

            $products.= ">";
            $products .= "<url>{$product['url']}</url>";
            $products .= "<price>{$product['price']}</price>";

            if(isset($product['oldprice']))
            {
                $products .= "<oldprice>{$product['oldprice']}</oldprice>";
            }

            if(!is_null($product['categories']))
            {
                $products .= "<categoryId>{$product['categories']}</categoryId>";
            }

            $products .= "<picture>{$product['picture']}</picture>";
            $products .= "<name>{$product['name']}</name>";


            if(isset($product['params']) && count($product['params']))
            {
                foreach ($product['params'] as $param)
                {
                    if($param['label'])
                        $products .= "<param name=\"{$param['code']}\">{$param['label']}</param>";
                }
            }


            $product['description'] = strip_tags($product['description']);

            if(strlen($product['description']) >= 200)
            {
                $product['description'] = substr($product['description'],0,200);
            }

            $product['description'] = $this->replaceXmlEntities($product['description']);

            if($this->hasHtml($product['description']))
            {
                $products .= "<description><![CDATA[{$product['description']}]]></description>";
            }
            else{
                $products .= "<description>{$product['description']}</description>";
            }

            if($product['model'])
            {
                $products .= "<model>{$product['model']}</model>";
            }

            if($product['vendor'])
            {
                $products .= "<vendor>{$product['vendor']}</vendor>";
            }

            $products .= "</offer>\n";
        }

        $products .= "</offers>";

        return $products;
    }

    public function buildFooter()
    {
        $footer = "</shop>\n";
        $footer .= "</yml_catalog>";

        return $footer;
    }

    /**
     * @param $string
     * @return string
     */
    public function replaceXmlEntities($string)
    {
        return strtr(
            $string,
            array(
                "<" => "&lt;",
                ">" => "&gt;",
                '"' => "&quot;",
                "'" => "&apos;",
                "&" => "&amp;",
            )
        );
    }

    /**
     * @param string $string
     * @return bool
     */
    public function hasHtml($string)
    {
        if($string != strip_tags($string))
        {
            return true;
        }

        return false;
    }
}

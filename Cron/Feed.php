<?php

namespace Improntus\RetailRocket\Cron;

use Exception;
use Improntus\RetailRocket\Helper\Data;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Tree\Node\Collection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\Write;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection as AttributeCollection;

/**
 * Class Feed
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
     * @var string
     */
    protected $_modelAttribute;

    /**
     * @var string
     */
    protected $_vendorAttribute;

    /**
     * @var array
     */
    protected $_extraAttributes = [];

    /**
     * @var AttributeCollection
     */
    protected $_attributeCollection;

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
        AttributeCollection $attributeCollection
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
        $this->_modelAttribute = $helper->getModelAttribute();
        $this->_vendorAttribute = $helper->getVendorAttribute();
        $this->_attributeCollection = $attributeCollection;

        $extraAttributes = $helper->getExtraAttributes();

        if($extraAttributes)
        {
            $attributeCodes = explode(',',$extraAttributes);

            $eavAttributeCollection = $this->_attributeCollection->getData();

            foreach ($attributeCodes as $attributeCode)
            {
                $type = null;

                foreach ($eavAttributeCollection as $_attribute)
                {
                    if($_attribute['attribute_code'] == $attributeCode)
                    {
                        $type = $_attribute['frontend_input'];
                    }
                }

                $this->_extraAttributes[$attributeCode] = $type;
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

        if($this->_modelAttribute)
        {
            $collection->addAttributeToSelect($this->_modelAttribute);
        }

        if($this->_vendorAttribute)
        {
            $collection->addAttributeToSelect($this->_vendorAttribute);
        }

        if(count($this->_extraAttributes))
        {
            foreach ($this->_extraAttributes as $extraAttribute => $type)
            {
                $collection->addAttributeToSelect($extraAttribute);
            }
        }

        $i = 0;

        foreach ($collection as $product)
        {
            if($product->getVisibility() == Visibility::VISIBILITY_NOT_VISIBLE)
            {
                continue;
            }

            if($product->getTypeId() == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE)
            {
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

            if($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
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

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $product->getProductUrl(),
                    'price' => (float)$finalPrice,
                    'picture' => $productImage,
                    'name' => $this->replaceXmlEntities($product->getName()),
                    'description' => $this->replaceXmlEntities($product->getData($this->_descriptionAttribute)),
                    'available' => $product->getIsSalable(),
                    'categories' => $product->getCategoryIds(),
                    'group_id' => null,
                    'params' => $params
                ];

                if($this->_modelAttribute)
                {
                    $result[$i][$this->_modelAttribute] = $this->replaceXmlEntities($product->getData($this->_modelAttribute));
                }

                if($this->_vendorAttribute)
                {
                    $result[$i][$this->_vendorAttribute] = $this->replaceXmlEntities($product->getData($this->_vendorAttribute));
                }

                $simpleProducts = $product->getTypeInstance()->getUsedProducts($product);

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

                    $result[$i] = [
                        'id' => $simpleProduct->getId(),
                        'url' => $simpleProduct->getProductUrl(),
                        'price' => (float)$simpleProduct->getFinalPrice(),
                        'picture' => $this->getProductImageUrl($simpleProduct->getImage(),$mediaStoreUrl),
                        'name' => $this->replaceXmlEntities($simpleProduct->getName()),
                        'description' => $this->replaceXmlEntities($product->getData($this->_descriptionAttribute)),
                        'available' => $simpleProduct->getIsSalable(),
                        'categories' => $simpleProduct->getCategoryIds(),
                        'group_id' => $groupId,
                        'params' => $params
                    ];

                    if($this->_modelAttribute)
                    {
                        $result[$i][$this->_modelAttribute] = $this->replaceXmlEntities($product->getData($this->_modelAttribute));
                    }

                    if($this->_vendorAttribute)
                    {
                        $result[$i][$this->_vendorAttribute] = $this->replaceXmlEntities($product->getData($this->_vendorAttribute));
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

                $result[$i] = [
                    'id' => $product->getId(),
                    'url' => $product->getProductUrl(),
                    'price' => $finalPrice,
                    'picture' => $productImage,
                    'name' => $this->replaceXmlEntities($product->getName()),
                    'description' => $this->replaceXmlEntities($product->getData($this->_descriptionAttribute)),
                    'available' => $product->getIsSalable(),
                    'categories' => $product->getCategoryIds(),
                    'group_id' => $groupId,
                    'params' => $params
                ];

                if($this->_modelAttribute)
                {
                    $result[$i][$this->_modelAttribute] = $this->replaceXmlEntities($product->getData($this->_modelAttribute));
                }

                if($this->_vendorAttribute)
                {
                    $result[$i][$this->_vendorAttribute] = $this->replaceXmlEntities($product->getData($this->_vendorAttribute));
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
        $categories = "<categories>";

        foreach ($this->_categories as $category)
        {
            $categories .= "<category id=\"{$category['id']}\"";

            if(!is_null($category['parentId']))
            {
                $categories.= " parentId=\"{$category['parentId']}\"";
            }

            $categories.= ">";
            $categories .= $category['name'];
            $categories .= "</category>";
        }

        $categories .= "</categories>";

        return $categories;
    }

    public function buildProducts()
    {
        $products = "<offers>";

        foreach ($this->_products as $product)
        {
            $products .= "<offer id=\"{$product['id']}\"";

            if($product['group_id'])
            {
                $products.= " groupId=\"{$product['group_id']}\"";
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

            foreach ($product['categories'] as $category)
            {
                $products .= "<categoryId>$category</categoryId>";
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

            $products .= "<description><![CDATA[{$product[$this->_descriptionAttribute]}]]></description>";

            if($this->_modelAttribute)
            {
                $products .= "<model>{$this->_modelAttribute}</model>";
            }

            if($this->_vendorAttribute)
            {
                $products .= "<vendor>{$this->_vendorAttribute}</vendor>";
            }

            $products .= "</offer>";
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
}

<?php

namespace Improntus\RetailRocket\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ExtraAttributes
 *
 * @Version 1.0.20
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\RetailRocket\Model\Config\Source
 */
class ExtraAttributes implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_attributeFactory;

    /**
     * ExtraAttributes constructor.
     * @param CollectionFactory $attributeFactory
     */
    public function __construct(
        CollectionFactory $attributeFactory
    )
    {
        $this->_attributeFactory = $attributeFactory;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributeData = [];
        $attributeInfo = $this->_attributeFactory->create();
        $attributeInfo->addFieldToFilter('attribute_code',
            ['nin'=>'name,sku,price,special_price,gift_message_available,links_title,links_exist,special_from_date,special_to_date,cost,meta_title,meta_keyword,meta_description,image,small_image,thumbnail,media_gallery,old_id,tier_price,gallery,visibility,custom_design,custom_design_from,custom_design_to,custom_layout_update,page_layout,category_ids,options_container,required_options,has_options,image_label,small_image_label,thumbnail_label,created_at,updated_at,country_of_manufacture,quantity_and_stock_status,custom_layout,msrp,msrp_display_actual_price_type,url_key,url_path,links_purchased_separately,swatch_image,shipment_type,meta_title,minimal_price,options_container,price_type,price_view,sku_type,status,tax_class_id,weight_type'])
            ->getData();

        $attributeData[] = [
            'value' => 0,
            'label' => __('None')
        ];

        foreach ($attributeInfo as $items)
        {
            $attributeData[] = [
                'value' => $items->getAttributeCode(),
                'label' => $items->getFrontendLabel()
            ];
        }

        return $attributeData;
    }
}

<?php

namespace Improntus\RetailRocket\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ExtraAttributes
 *
 * @version 1.0.6
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Model\Config\Source
 */
class ImageType implements ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $attributeData = [
            [
                'value' => 'category_page_grid',
                'label' => __('Category page grid')
            ],
            [
                'value' => 'category_page_list',
                'label' => __('Category page list')
            ]
        ];

        return $attributeData;
    }
}

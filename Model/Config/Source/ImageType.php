<?php

namespace Improntus\RetailRocket\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ExtraAttributes
 *
 * @version 1.0.14
 * @author Improntus <https://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Model\Config\Source
 */
class ImageType implements OptionSourceInterface
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

<?php

namespace Improntus\RetailRocket\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ExtraAttributes
 *
 * @Version 1.0.18
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Solution Partner
 * @copyright Copyright (c) 2024 Improntus
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
        return [
            [
                'value' => 'category_page_grid',
                'label' => __('Category page grid')
            ],
            [
                'value' => 'category_page_list',
                'label' => __('Category page list')
            ]
        ];
    }
}

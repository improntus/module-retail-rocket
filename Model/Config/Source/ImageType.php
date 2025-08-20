<?php

namespace Improntus\RetailRocket\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ExtraAttributes
 *
 * @version 1.0.21
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
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

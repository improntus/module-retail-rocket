<?php

namespace Improntus\RetailRocket\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class QtyCategories
 *
 * @Version 1.0.18
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Solution Partner
 * @copyright Copyright (c) 2024 Improntus
 * @package Improntus\RetailRocket\Model\Config\Source
 */
class QtyCategories implements OptionSourceInterface
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
        $attributeData[] = [
            'value' => 1,
            'label' => __('Last category')
        ];

        $attributeData[] = [
            'value' => 2,
            'label' => __('Last 2 categories')
        ];

        return $attributeData;
    }
}

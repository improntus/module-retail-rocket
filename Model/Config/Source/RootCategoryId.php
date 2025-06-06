<?php

namespace Improntus\RetailRocket\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class RootCategoryId
 *
 * @Version 1.0.19
 * @author Improntus <https://www.improntus.com> - Elevating Digital Experience | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\RetailRocket\Model\Config\Source
 */
class RootCategoryId implements OptionSourceInterface
{
    /**
     * Category collection factory
     *
     * @var CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * RootCategoryId constructor.
     * @param CollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CollectionFactory $categoryCollectionFactory
    )
    {
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * Options getter
     *
     * @return array
     * @throws LocalizedException
     */
    public function toOptionArray()
    {
        /** @var Collection $collection */
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name')->addRootLevelFilter()->load();

        $options = [];

        foreach ($collection as $category) {
            $options[] = ['label' => $category->getName(), 'value' => $category->getId()];
        }

        return $options;
    }
}

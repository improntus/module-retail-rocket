<?php

namespace Improntus\RetailRocket\Model\Config\Source;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class CategoryTree
 *
 * @version 1.0.13
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2020 Improntus
 * @package Improntus\RetailRocket\Model\Config\Source
 */
class CategoryTree implements ArrayInterface
{
    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @param CategoryCollectionFactory $categoryCollectionFactory
     */
    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }

    /**
     * {@inheritdoc}
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray()
    {
        return $this->getCategoryTree();
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCategoryTree()
    {
        $orderedTree = [];
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToSelect('name');
        $collection->addAttributeToSelect(['name', 'parent_id']);
        $this->getCategoryOptions($collection,0, $orderedTree);

        return $orderedTree;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Category\Collection $collection
     * @param int                                                      $parentId
     * @param array                                                    $nodes
     */
    protected function getCategoryOptions(\Magento\Catalog\Model\ResourceModel\Category\Collection $collection, $parentId = 0, &$nodes = [])
    {
        foreach ($collection as $category) {
            if ($category->getParentId() == $parentId) {
                $space = str_repeat("&nbsp;", $category->getLevel() * 4);
                $node = [
                    'value' => $category->getEntityId(),
                    'label' => $space . $category->getName()
                ];
                $nodes[] = $node;
                $this->getCategoryOptions($collection, $category->getEntityId(), $nodes);
            }
        }
    }
}

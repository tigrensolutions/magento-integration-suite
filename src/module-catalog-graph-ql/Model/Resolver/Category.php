<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Resolver;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\TreeFactory;
use Magento\CatalogGraphQl\Model\Category\Hydrator;
use Magento\CatalogGraphQl\Model\Resolver\Category\CheckCategoryIsActive;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Tigren\Core\Helper\Data;

/**
 * Class Category
 * @package Tigren\CatalogGraphQl\Model\Resolver
 */
class Category implements ResolverInterface
{
    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var TreeFactory
     */
    private $treeResourceFactory;

    /**
     * @var CheckCategoryIsActive
     */
    private $checkCategoryIsActive;

    /**
     * @var Hydrator
     */
    private $categoryHydrator;

    /**
     * Category constructor.
     * @param Data $helper
     * @param CategoryFactory $categoryFactory
     * @param CheckCategoryIsActive $checkCategoryIsActive
     * @param Hydrator $categoryHydrator
     * @param TreeFactory|null $treeResourceFactory
     */
    public function __construct(
        Data $helper,
        CategoryFactory $categoryFactory,
        CheckCategoryIsActive $checkCategoryIsActive,
        Hydrator $categoryHydrator,
        TreeFactory $treeResourceFactory = null
    ) {
        $this->helper = $helper;
        $this->categoryFactory = $categoryFactory;
        $this->checkCategoryIsActive = $checkCategoryIsActive;
        $this->categoryHydrator = $categoryHydrator;
        $this->treeResourceFactory = $treeResourceFactory ?? ObjectManager::getInstance()
                ->get(TreeFactory::class);
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (isset($value[$field->getName()])) {
            return $value[$field->getName()];
        }

        $categoryId = $this->getCategoryId($args);
        if ($categoryId !== \Magento\Catalog\Model\Category::TREE_ROOT_ID) {
            $this->checkCategoryIsActive->execute($categoryId);
        }

        $category = $this->categoryFactory->create()->load($categoryId);
        $categoryData = $this->categoryHydrator->hydrateCategory($category);

        return $this->helper->applyMetaConfig($categoryData, 'cat');
    }

    /**
     * Get category id
     *
     * @param array $args
     * @return int
     * @throws GraphQlInputException
     */
    private function getCategoryId(array $args): int
    {
        if (!isset($args['id'])) {
            throw new GraphQlInputException(__('"id for category should be specified'));
        }

        return (int)$args['id'];
    }
}

<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Graphql\Resolver\Catalog\Category;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\FilterList;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogSearch\Model\Layer\Filter\Attribute;
use Magento\CatalogSearch\Model\Layer\Filter\Decimal;
use Magento\CatalogSearch\Model\Layer\Filter\Price;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Tigren\CatalogGraphQl\Helper\Data;

/**
 * @inheritdoc
 */
class Filters implements ResolverInterface
{
    /**
     *
     */
    const CATEGORY_FILTER = 'category';
    /**
     *
     */
    const ATTRIBUTE_FILTER = 'attribute';
    /**
     *
     */
    const PRICE_FILTER = 'price';
    /**
     *
     */
    const DECIMAL_FILTER = 'decimal';

    /**
     * @var
     */
    protected $_layerResolver;
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;
    /**
     * @var RequestInterface
     */
    protected $_request;
    /**
     * @var Data
     */
    protected $_helper;
    /**
     * @var Escaper
     */
    protected $_escaper;
    /**
     * @var SwatchHelper
     */
    protected $swatchHelper;
    /**
     * @var array
     */
    protected $filters = [
        self::CATEGORY_FILTER => \Magento\CatalogSearch\Model\Layer\Filter\Category::class,
        self::ATTRIBUTE_FILTER => Attribute::class,
        self::PRICE_FILTER => Price::class,
        self::DECIMAL_FILTER => Decimal::class,
    ];
    /**
     * @var
     */
    private $_categoryId;
    /**
     * @var
     */
    private $_category;
    /**
     * @var array
     */
    private $data = [];

    /**
     * Filters constructor.
     * @param Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryFactory $categoryFactory
     * @param RequestInterface $request
     * @param Data $helper
     * @param Escaper $escaper
     * @param SwatchHelper $swatchHelper
     */
    public function __construct(
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        CategoryFactory $categoryFactory,
        RequestInterface $request,
        Data $helper,
        Escaper $escaper,
        SwatchHelper $swatchHelper
    ) {
        $this->_layerResolver = $layerResolver->get();
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->_request = $request;
        $this->_helper = $helper;
        $this->_escaper = $escaper;
        $this->swatchHelper = $swatchHelper;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->_categoryId = $this->_getCategoryId($value);

        if (empty($value['model'])) {
            return [];
        }

        $displayMode = $value['model']->getData('display_mode');
        if ($displayMode && $displayMode != Category::DM_PRODUCT) {
            return [];
        }

        $request = $this->getRequest();
        $params = $request->getParams();
        $params['id'] = $this->_categoryId;
        if (!empty($args['filter'])) {
            foreach ($args['filter'] as $layerFilterInput) {
                $params[$layerFilterInput['code']] = $layerFilterInput['value'];
            }
        }
        $request->setParams($params);

        $this->_layerResolver->setCurrentCategory($this->_categoryId);
        $objectManager = ObjectManager::getInstance();
        $fill = $objectManager->create('Magento\Catalog\Model\Layer\Category\FilterableAttributeList');
        $filterList = new FilterList($objectManager, $fill, $this->filters);

        $this->data['items'] = [];

        foreach ($filterList->getFilters($this->_layerResolver) as $filter) {
            $filter->apply($request);
        }
        $this->getLayer()->apply();

        $colorAttrs = ['fashion_color', 'color'];

        foreach ($filterList->getFilters($this->_layerResolver) as $filter) {
            $code = (string)$filter->getRequestVar();
            $filterOption = [];
            $filterOption['class'] = get_class($filter);
            $filterOption['name'] = (string)$filter->getName();
            $filterOption['code'] = $code;
            $filterOption['items'] = [];

            foreach ($filter->getItems() as $item) {
                $filterOption['items'][] = [
                    'value' => $item->getValue(),
                    'name' => htmlspecialchars_decode(gettype($item->getLabel()) === 'object'
                        ? $item->getLabel()->render() : $item->getLabel()),
                    'count' => $item->getCount(),
                    'text' => in_array($code, $colorAttrs) ? $this->getAtributeSwatchHashcode($item->getValue()) : ''
                ];
            }
            if (!empty($filterOption['items'])) {
                $this->data['items'][] = $filterOption;
            }
        }
        $this->data['items'] = $this->data['items'] ?? null;

        return $this->data;
    }

    /**
     * @param array $args
     * @return int
     * @throws GraphQlInputException
     */
    private function _getCategoryId(array $value): int
    {
        if (!isset($value['id'])) {
            throw new GraphQlInputException(__('"id for category should be specified'));
        }

        return (int)$value['id'];
    }

    /**
     * Get request
     *
     * @return RequestInterface
     */
    private function getRequest()
    {
        return $this->_request;
    }

    /**
     * Get layer object
     *
     * @return Layer
     */
    private function getLayer()
    {
        return $this->_layerResolver;
    }

    /**
     * @param $optionId
     * @return |null
     */
    public function getAtributeSwatchHashcode($optionId)
    {
        $hashcodeData = $this->swatchHelper->getSwatchesByOptionsId([$optionId]);
        return $hashcodeData[$optionId]['value'] ?? null;
    }

    /**
     * @return mixed
     */
    protected function getCategory()
    {
        if (!$this->_category) {
            $this->_category = $this->_productFactory->create()->load($this->_productId);
        }
        return $this->_category;
    }
}

<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Graphql\Resolver\Category;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Escaper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Search\Model\QueryFactory;
use Magento\Swatches\Helper\Data as SwatchHelper;
use Tigren\Core\Helper\Data as CoreHelper;
use Magento\Catalog\Model\Layer\FilterList;

/**
 * @inheritdoc
 */
class Filters implements ResolverInterface
{
    /**
     * @var Resolver
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
     * @var Escaper
     */
    protected $_escaper;
    /**
     * @var SwatchHelper
     */
    protected $swatchHelper;
    /**
     * @var FilterList
     */
    protected $filterList;
    /**
     * @var
     */
    protected $queryText;
    /**
     * @var
     */
    protected $layer;
    /**
     * @var QueryFactory
     */
    protected $queryFactory;
    /**
     * @var CoreHelper
     */
    private $coreHelper;
    /**
     * @var Config
     */
    private $configHelper;
    /**
     * @var
     */
    private $queryModel;
    /**
     * @var
     */
    private $_categoryId;
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
     * @param Escaper $escaper
     * @param SwatchHelper $swatchHelper
     * @param FilterList $filterList
     * @param CoreHelper $coreHelper
     * @param QueryFactory $queryFactory
     */
    public function __construct(
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        CategoryFactory $categoryFactory,
        RequestInterface $request,
        Escaper $escaper,
        SwatchHelper $swatchHelper,
        FilterList $filterList,
        CoreHelper $coreHelper,
        QueryFactory $queryFactory
    ) {
        $this->_layerResolver = $layerResolver;
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->_request = $request;
        $this->_escaper = $escaper;
        $this->swatchHelper = $swatchHelper;
        $this->filterList = $filterList;
        $this->coreHelper = $coreHelper;
        $this->queryFactory = $queryFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $this->_categoryId = $this->_getCategoryId($value);
        if (empty($value['model']) ||
            $value['model']->getData('display_mode') == Category::DM_PAGE
        ) {
            return [];
        }
        $this->setRequestParams($args);

        $layerResolver = $this->getLayer();
        $layerResolver->setCurrentCategory($this->_categoryId);
        foreach ($this->filterList->getFilters($layerResolver) as $filter) {
            $filter->apply($this->_request);
        }
        $layerResolver->apply();

        $rootCategoryId = $this->coreHelper->getRootCategoryId();
        $items = [];
        $colorAttrs = ['fashion_color', 'color'];
        foreach ($this->filterList->getFilters($layerResolver) as $filter) {
            $code = (string)$filter->getRequestVar();
            if ($code == 'brand' && $this->_categoryId == $rootCategoryId) {
                continue;
            }
            $filterOption = [];
            $filterOption['class'] = get_class($filter);
            $filterOption['name'] = (string)$filter->getName();
            $filterOption['code'] = $code;
            $filterOption['is_multiselect'] = $this->isMultiSelect($filter, $code);
            $filterOption['items'] = [];

            foreach ($filter->getItems() as $item) {
                $filterOption['items'][] = [
                    'value' => $item->getValue(),
                    'name' => htmlspecialchars_decode(gettype($item->getLabel()) === 'object'
                        ? $item->getLabel()->render() : $item->getLabel()),
                    'count' => $item->getCount(),
                    'text' => in_array(
                        $code,
                        $colorAttrs
                    ) ? $this->getAtributeSwatchHashcode($item->getValue()) : ''
                ];
            }
            if (!empty($filterOption['items'])) {
                $items[] = $filterOption;
            }
        }
        $result = [
            'items' => $items ?: null
        ];
        return $result;
    }

    /**
     * @param array $value
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
     * @param $args
     */
    protected function setRequestParams($args)
    {
        $params = $this->_request->getParams();
        $params['id'] = $this->_categoryId;
        if (!empty($args['filter'])) {
            foreach ($args['filter'] as $layerFilterInput) {
                if ($layerFilterInput['code'] == 'query') {
                    $this->queryText = $layerFilterInput['value'];
                } else {
                    $params[$layerFilterInput['code']] = $layerFilterInput['value'];
                }
            }
        }
        $this->_request->setParams($params);
    }

    /**
     * Get layer object
     *
     * @return Layer
     */
    private function getLayer()
    {
        if (!$this->layer) {
            if ($this->queryText) {
                $this->_layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);
                $this->_request->setParam('q', $this->queryText);
                $this->queryModel = $this->queryFactory->get();
            }
            $this->layer = $this->_layerResolver->get();
        }
        return $this->layer;
    }

    /**
     * @param $filter
     * @param $code
     * @return bool
     */
    public function isMultiSelect($filter, $code)
    {
        return false;
    }

    /**
     * @param $optionId
     * @return string|null
     */
    public function getAtributeSwatchHashcode($optionId)
    {
        $hashcodeData = $this->swatchHelper->getSwatchesByOptionsId([$optionId]);
        return $hashcodeData[$optionId]['value'] ?? null;
    }

}

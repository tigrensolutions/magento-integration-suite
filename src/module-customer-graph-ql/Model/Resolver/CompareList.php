<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Model\Stock\StockItemRepository;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Tigren\CustomerGraphQl\Helper\Data;

/**
 * Class CustomerWishlist
 * @package Tigren\CustomerGraphQl\Model\Resolver
 */
class CompareList implements ResolverInterface
{
    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StockItemRepository
     */
    protected $_stockItemRepository;

    /**
     * CompareList constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param Data $helper
     * @param StockItemRepository $stockItemRepository
     * @param Session $session
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        Data $helper,
        StockItemRepository $stockItemRepository,
        Session $session
    ) {
        $this->productRepository = $productRepository;
        $this->_customerSession = $session;
        $this->_helper = $helper;
        $this->_stockItemRepository = $stockItemRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        $customerId = null;
        $visitorId = null;
        if (!$context->getUserId()) {
            $sessionId = $this->_customerSession->getSessionId();
            $visitorId = $this->_helper->getVisitorId($sessionId);
        } else {
            $customerId = $context->getUserId();
        }
        $compareCollection = $this->_helper->getCompareCollection($customerId, $visitorId);
        $compareData = [];
        foreach ($compareCollection as $product) {
            $compareData[] = $this->getItemData($product);
        }
        return $compareData ?: null;
    }

    /**
     * @param Product $product
     * @return array
     * @throws LocalizedException
     */
    private function getItemData($product)
    {
        $stockData = $this->_stockItemRepository->get($product->getId());

        return [
            'id' => $product->getId(),
            'sku' => $product->getSku(),
            'currency' => 'USD', // hardcode temporarily
            'small_image' => $product->getImage(),
            'url_key' => $product->getUrlKey(),
            'name' => $product->getName(),
            'price' => $product->getPrice() ?: $product->getFinalPrice(),
            'special_price' => $product->getSpecialPrice(),
            'final_price' => $product->getFinalPrice(),
            'type_id' => $product->getTypeId(),
            'is_available' => $stockData->getIsInStock(),
            'description' => $product->getDescription() ?: '',
            'attributes' => $this->getAttributeData($product)
        ];
    }

    /**
     * @param $product
     * @return array
     */
    private function getAttributeData($product)
    {
        $attributes = $this->_helper->getAttributes();
        $result = [];
        if ($attributes) {
            foreach ($attributes as $attribute) {
                $value = $this->_helper->getProductAttributeValue($product, $attribute);
                $result[] = [
                    'code' => $attribute->getAttributeCode(),
                    'value' => $value,
                    'label' => $attribute->getStoreLabel()
                ];
            }
        }
        return $result;
    }
}

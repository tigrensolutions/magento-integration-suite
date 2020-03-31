<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CompareGraphQl\Model\Resolver\Mutation;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class AddToCart
 * @package Tigren\CompareGraphQl\Model\Resolver\Mutation
 */
class AddToCart implements ResolverInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Cart
     */
    protected $cart;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var
     */
    private $optionFactory;

    /**
     * AddToCart constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param EventManager $eventManager
     * @param Cart $cart
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        EventManager $eventManager,
        Cart $cart,
        StoreManagerInterface $storeManager
    ) {
        $this->productRepository = $productRepository;
        $this->cart = $cart;
        $this->_storeManager = $storeManager;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return bool|Value|mixed
     * @throws GraphQlInputException
     * @throws ProductException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['item'])) {
            throw new GraphQlInputException(__('Specify the "item" value.'));
        }

        $itemId = $args['item'];
        $params = [];
        $params['qty'] = 1;

        $storeId = $this->_storeManager->getStore()->getId();
        $product = $this->productRepository->getById($itemId, false, $storeId);
        if (!$product) {
            throw new ProductException(__('This product(s) is out of stock.'));
        }
        try {

            $this->cart->addProduct($product, $params);
        } catch (ProductException $e) {
            throw new ProductException(__('This product(s) is out of stock.'));
        }

        return true;
    }
}

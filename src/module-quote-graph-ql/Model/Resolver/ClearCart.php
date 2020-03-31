<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\QuoteGraphQl\Model\Resolver;

use Exception;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;

/**
 * Class ClearCart
 * @package Tigren\QuoteGraphQl\Model\Resolver
 */
class ClearCart implements ResolverInterface
{
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * ClearCart constructor.
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteFactory $quoteFactory
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteFactory = $quoteFactory;
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
        if (!isset($args['cartId'])) {
            throw new GraphQlInputException(__('Specify the "cartId" value.'));
        }
        $cartId = $args['cartId'];

        $currentUserId = $context->getUserId();
        $currentUserType = $context->getUserType();

        $quote = $this->quoteFactory->create();

        if ($this->isUserGuest($currentUserId, $currentUserType)) {
            /** @var $quoteIdMask QuoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quote->load($quoteIdMask->getQuoteId());
        } else {
            $quote->loadByCustomer($currentUserId);
        }
        if (!$quote->getId() || ($currentUserId && $quote->getId() != $cartId)) {
            throw new GraphQlInputException(__('Please check again input data.'));
        }
        try {
            $quote->removeAllItems()->collectTotals()->save();
        } catch (Exception $e) {
            throw new GraphQlInputException(__('Can not clear this cart'));
        }

        return true;
    }

    /**
     * Checking if current customer is guest
     *
     * @param int|null $customerId
     * @param int|null $customerType
     * @return bool
     */
    private function isUserGuest(?int $customerId, ?int $customerType): bool
    {
        if (null === $customerId || null === $customerType) {
            return true;
        }
        return 0 === (int)$customerId || (int)$customerType === UserContextInterface::USER_TYPE_GUEST;
    }

}

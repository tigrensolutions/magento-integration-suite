<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\StoreGraphQl\Model\Resolver;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteFactory;

/**
 * {@inheritdoc}
 */
class CollectTotals implements ResolverInterface
{
    /**
     * Quote repository.
     *
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * CollectTotals constructor.
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param StoreManagerInterface $storeManager
     * @param QuoteFactory $quoteFactory
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        StoreManagerInterface $storeManager,
        QuoteFactory $quoteFactory
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($args['cart_id'])) {
            throw new LocalizedException(__('"cart_id" value should be specified'));
        }
        $cartId = $args['cart_id'];
        if (!$context->getUserId()) {
            /** @var $quoteIdMask QuoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $cartId = $quoteIdMask->getQuoteId();
        }
        try {
            /** @var Quote $quote */
            $quote = $this->quoteFactory->create()->load($cartId);
            $quoteCurrency = $quote->getQuoteCurrencyCode();
            $currentCurrency = $this->storeManager->getStore()->getCurrentCurrencyCode();
            if ($quoteCurrency != $currentCurrency) {
                $this->quoteRepository->save($quote->collectTotals());
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
}

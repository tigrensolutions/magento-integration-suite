<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\QuoteGraphQl\Override\Magento\QuoteGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\TotalsCollector;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * @inheritdoc
 */
class CartPrices extends \Magento\QuoteGraphQl\Model\Resolver\CartPrices
{
    /**
     * @var TotalsCollector
     */
    private $totalsCollector;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * CartPrices constructor.
     * @param StoreManagerInterface $storeManager
     * @param TotalsCollector $totalsCollector
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        TotalsCollector $totalsCollector
    ) {
        parent::__construct($totalsCollector);
        $this->totalsCollector = $totalsCollector;
        $this->_storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Quote $quote */
        $quote = $value['model'];
        $cartTotals = $this->totalsCollector->collectQuoteTotals($quote);

        if ($quote->hasForcedCurrency()) {
            $quoteCurrency = $quote->getForcedCurrency();
        } else {
            $quoteCurrency = $this->getStore()->getCurrentCurrency();
        }
        $currency = $quoteCurrency->getCode();

        return [
            'shipping_including_tax' => ['value' => $cartTotals->getShippingInclTax(), 'currency' => $currency],
            'shipping_excluding_tax' => ['value' => $cartTotals->getShippingAmount(), 'currency' => $currency],
            'subtotal_including_tax' => ['value' => $cartTotals->getSubtotalInclTax(), 'currency' => $currency],
            'subtotal_excluding_tax' => ['value' => $cartTotals->getSubtotal(), 'currency' => $currency],
            'subtotal_with_discount_excluding_tax' => [
                'value' => $cartTotals->getSubtotalWithDiscount(),
                'currency' => $currency
            ],
            'applied_taxes' => $this->getAppliedTaxes($cartTotals, $currency),
            'discount' => $this->getDiscount($cartTotals, $currency),
            'grand_total' => ['value' => $cartTotals->getGrandTotal(), 'currency' => $currency],
            'model' => $quote
        ];
    }

    /**
     * Returns taxes applied to the current quote
     *
     * @param Total $total
     * @param string $currency
     * @return array
     */
    private function getAppliedTaxes(Total $total, string $currency): array
    {
        $appliedTaxesData = [];
        $appliedTaxes = $total->getAppliedTaxes();

        if (empty($appliedTaxes)) {
            return $appliedTaxesData;
        }

        foreach ($appliedTaxes as $appliedTax) {
            $appliedTaxesData[] = [
                'label' => $appliedTax['id'],
                'amount' => ['value' => $appliedTax['amount'], 'currency' => $currency]
            ];
        }
        return $appliedTaxesData;
    }

    /**
     * Returns information about an applied discount
     *
     * @param Total $total
     * @param string $currency
     * @return array|null
     */
    private function getDiscount(Total $total, string $currency)
    {
        if ($total->getDiscountAmount() === 0) {
            return null;
        }
        return [
            'label' => explode(', ', $total->getDiscountDescription()),
            'amount' => ['value' => $total->getDiscountAmount(), 'currency' => $currency]
        ];
    }

    /**
     * Get quote store model object
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    public function getStore()
    {
        return $this->_storeManager->getStore();
    }
}

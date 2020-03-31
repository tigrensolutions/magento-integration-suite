<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\StoreGraphQl\Model\Resolver;

use Magento\Directory\Model\CurrencyFactory;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Locale\Bundle\CurrencyBundle as CurrencyBundle;
use Magento\Store\Model\StoreManagerInterface;

/**
 * {@inheritdoc}
 */
class Currency implements ResolverInterface
{
    /**
     * @var CurrencyFactory
     */
    protected $_currencyFactory;

    /**
     * @var PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    /**
     * Store manager
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param CurrencyFactory $currencyFactory
     * @param PostHelper $postDataHelper
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CurrencyFactory $currencyFactory,
        PostHelper $postDataHelper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        StoreManagerInterface $storeManager
    ) {
        $this->_currencyFactory = $currencyFactory;
        $this->_postDataHelper = $postDataHelper;
        $this->localeResolver = $localeResolver;
        $this->_storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currencies = [];
        $codes = $this->_storeManager->getStore()->getAvailableCurrencyCodes(true);
        if (is_array($codes)) {
            $rates = $this->_currencyFactory->create()->getCurrencyRates(
                $this->_storeManager->getStore()->getBaseCurrency(),
                $codes
            );
            foreach ($codes as $code) {
                if (isset($rates[$code])) {
                    $allCurrencies = (new CurrencyBundle())->get(
                        $this->localeResolver->getLocale()
                    )['Currencies'];
                    $currencies[] = [
                        'code' => $code,
                        'name' => $allCurrencies[$code][1] ?: $code
                    ];
                }
            }
        }
        return $currencies;
    }
}

<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\CustomerGraphQl\Model\Customer\GetCustomer;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\QuoteRepository\SaveHandler;


/**
 * Class AssignGuestCart
 * @package Tigren\CustomerGraphQl\Model\Resolver\Mutation
 */
class AssignGuestCart implements ResolverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;
    /**
     * @var QuoteFactory
     */
    protected $quoteFactory;
    /**
     * @var SaveHandler
     */
    protected $saveHandler;
    /**
     * @var GetCustomer
     */
    private $getCustomer;

    /**
     * AssignGuestCart constructor.
     * @param GetCustomer $getCustomer
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param QuoteFactory $quoteFactory
     * @param SaveHandler $saveHandler
     */
    public function __construct(
        GetCustomer $getCustomer,
        CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        QuoteFactory $quoteFactory,
        SaveHandler $saveHandler
    ) {
        $this->getCustomer = $getCustomer;
        $this->quoteRepository = $quoteRepository;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->quoteFactory = $quoteFactory;
        $this->saveHandler = $saveHandler;
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
        $currentUserId = $context->getUserId();
        $currentUserType = $context->getUserType();
        $customer = $this->getCustomer->execute($currentUserId, $currentUserType);
        $currentUserId = (int)$currentUserId;

        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($args['cartId'], 'masked_id');
        $guestQuote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
        $quote = $this->quoteFactory->create()->loadByCustomer($customer->getId());

        if ($quote->merge($guestQuote)) {
            try {
                $this->saveHandler->save($quote);
                $quote->collectTotals();
            } catch (Exception $e) {
                throw new CouldNotSaveException(__($e->getMessage()));
            }
        } else {
            return false;
        }

        return true;
    }

}

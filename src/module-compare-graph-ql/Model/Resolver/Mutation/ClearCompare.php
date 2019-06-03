<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */
declare(strict_types=1);

namespace Tigren\CompareGraphQl\Model\Resolver\Mutation;

use Exception;
use Magento\Catalog\Helper\Product\Compare;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Compare\Item\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\ObjectManagerInterface;
use Tigren\CompareGraphQl\Helper\Data;

/**
 * Class ClearCompare
 * @package Tigren\CompareGraphQl\Model\Resolver\Mutation
 */
class ClearCompare implements ResolverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Customer session
     *
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var CollectionFactory
     */
    protected $_itemCollectionFactory;

    /**
     * ClearCompare constructor.
     * @param ObjectManagerInterface $objectManager
     * @param Session $session
     * @param CollectionFactory $itemCollectionFactory
     * @param Data $helper
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Session $session,
        CollectionFactory $itemCollectionFactory,
        Data $helper
    ) {
        $this->_itemCollectionFactory = $itemCollectionFactory;
        $this->_helper = $helper;
        $this->_customerSession = $session;
        $this->_objectManager = $objectManager;
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
        /** @var Collection $items */
        $items = $this->_itemCollectionFactory->create();
        $customerId = $this->_customerSession->getCustomerId();
        if ($customerId) {
            $items->setCustomerId($customerId);
        } else {
            $sessionId = $this->_customerSession->getSessionId();
            $visitorId = $this->_helper->getVisitorId($sessionId);
            $items->setVisitorId($visitorId);
        }
        try {
            $items->clear();
            $this->_objectManager->get(Compare::class)->calculate();
        } catch (LocalizedException $e) {
        } catch (Exception $e) {
        }
        return true;
    }

}

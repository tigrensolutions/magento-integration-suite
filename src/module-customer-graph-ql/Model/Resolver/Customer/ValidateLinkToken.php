<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CustomerGraphQl\Model\Resolver\Customer;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\ExpiredException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\Value;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

class ValidateLinkToken implements ResolverInterface
{
    /**
     * @var AccountManagementInterface
     */
    protected $accountManagement;

    /**
     * ValidateLinkToken constructor.
     * @param AccountManagementInterface $accountManagement
     */
    public function __construct(
        AccountManagementInterface $accountManagement
    ) {
        $this->accountManagement = $accountManagement;
    }


    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return bool|Value|mixed
     * @throws GraphQlInputException
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($args['token'])) {
            throw new GraphQlInputException(__('Specify the "item" value.'));
        }

        try {
            $this->accountManagement->validateResetPasswordLinkToken(null, $args['token']);
            return true;
        } catch (InputException $e) {
        } catch (NoSuchEntityException $e) {
        } catch (ExpiredException $e) {
        } catch (InputMismatchException $e) {
        } catch (LocalizedException $e) {
        }
    }

}

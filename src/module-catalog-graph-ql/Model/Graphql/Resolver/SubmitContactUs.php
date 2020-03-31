<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

declare(strict_types=1);

namespace Tigren\CatalogGraphQl\Model\Graphql\Resolver;

use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Exception\GraphQlAuthenticationException;
use Magento\Contact\Model\MailInterface;
use Magento\Framework\DataObject;

/**
 * Class SubmitContactUs
 * @package Tigren\CatalogGraphQl\Model\Graphql\Resolver
 */
class SubmitContactUs implements ResolverInterface
{
    /**
     * @var MailInterface
     */
    private $mail;

    /**
     * @param MailInterface $mail
     */
    public function __construct(
        MailInterface $mail
    ) {
        $this->mail = $mail;
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
        if (!isset($args['name'])) {
            throw new GraphQlInputException(__('Specify the "name" value.'));
        }

        if (!isset($args['email'])) {
            throw new GraphQlInputException(__('Specify the "email" value.'));
        }

        if (!isset($args['comment'])) {
            throw new GraphQlInputException(__('Specify the "comment" value.'));
        }

        $this->sendEmail($args);
        return true;
    }

    /**
     * @param $post
     */
    private function sendEmail($post)
    {
        $this->mail->send(
            $post['email'],
            ['data' => new DataObject($post)]
        );
    }
}

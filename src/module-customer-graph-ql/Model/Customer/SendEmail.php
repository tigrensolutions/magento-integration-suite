<?php
/**
 * @author Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license Open Software License ("OSL") v. 3.0
 */

namespace Tigren\CustomerGraphQL\Model\Customer;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\SendFriend\Model\SendFriend;
use Tigren\CustomerGraphQl\Api\Customer\SendEmailInterface;

class SendEmail implements SendEmailInterface
{

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SendFriend
     */
    protected $sendFriend;

    /**
     * SendEmail constructor.
     * @param ProductRepositoryInterface $productRepository
     * @param SendFriend $sendFriend
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SendFriend $sendFriend
    ) {
        $this->productRepository = $productRepository;
        $this->sendFriend = $sendFriend;
    }

    /**
     * @param mixed $sendData
     * @return bool|mixed
     * @throws NoSuchEntityException
     */
    public function sendEmailToFriend($sendData)
    {
        if (!$sendData['item'] && !$sendData['invitee'] && !$sendData['message'] && !$sendData['customer_email'] && !$sendData['customer_name']) {
            return false;
        }
        $product = $this->productRepository->getById($sendData['item']);
        $sender = [
            'name' => $sendData['customer_name'],
            'email' => $sendData['customer_email'],
            'message' => $sendData['message']
        ];
        $recipents = $sendData['invitee'];
        $name = [];
        $email = [];
        foreach ($recipents as $key => $recipent) {
            $name[$key] = $recipent['name'];
            $email[$key] = $recipent['email'];
        }
        $recipents = ['name' => $name, 'email' => $email];
        $this->sendFriend->setSender($sender);
        $this->sendFriend->setRecipients($recipents);
        $this->sendFriend->setProduct($product);
        try {
            $validate = $this->sendFriend->validate();
            if ($validate === true) {
                $this->sendFriend->send();
                return true;
            }
        } catch (LocalizedException $e) {
        } catch (Exception $e) {
        }
        return true;
    }
}

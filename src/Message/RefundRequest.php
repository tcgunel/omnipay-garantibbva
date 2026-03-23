<?php

namespace Omnipay\Garantibbva\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Garantibbva\Constants\TransactionTypes;
use Omnipay\Garantibbva\Helpers\Helper;
use Omnipay\Garantibbva\Traits\PurchaseGettersSetters;

class RefundRequest extends AbstractRequest
{
    use PurchaseGettersSetters;

    protected $test_endpoint = 'https://sanalposprovtest.garantibbva.com.tr/VPServlet';

    protected $prod_endpoint = 'https://sanalposprov.garanti.com.tr/VPServlet';

    /**
     * @throws InvalidRequestException
     */
    public function getData()
    {
        $this->validate(
            'terminal_user_id',
            'terminal_merchant_id',
            'terminal_id',
            'refund_user_password',
            'amount',
            'currency',
            'transactionId',
            'testMode',
            'client_ip',
        );

        return [
            'Mode' => $this->getTestMode() ? 'TEST' : 'PROD',
            'Version' => $this->getApiVersion() ?? 'v0.01',
            'Terminal' => [
                'ProvUserID' => 'PROVRFN',
                'UserID' => $this->getTerminalUserId(),
                'HashData' => $this->hash(),
                'ID' => $this->getTerminalId(),
                'MerchantID' => $this->getTerminalMerchantId(),
            ],
            'Customer' => [
                'IPAddress' => $this->getClientIp(),
                'EmailAddress' => $this->getCard()?->getEmail() ?? '',
            ],
            'Order' => [
                'OrderID' => $this->getTransactionId(),
            ],
            'Transaction' => [
                'Type' => TransactionTypes::REFUND,
                'Amount' => $this->getAmountInteger(),
                'CurrencyCode' => $this->getCurrencyNumeric(),
            ],
        ];
    }

    private function hash(): string
    {
        $hashPasswordData = [
            $this->getRefundUserPassword(),
            str_pad((int) $this->getTerminalId(), 9, 0, STR_PAD_LEFT),
        ];

        $hashedPassword = strtoupper(sha1(implode('', $hashPasswordData)));

        $hashedDataArr = [
            $this->getTransactionId(),
            $this->getTerminalId(),
            $this->getAmountInteger(),
            $this->getCurrencyNumeric(),
            $hashedPassword,
        ];

        return strtoupper(hash('sha512', implode('', $hashedDataArr)));
    }

    private function prepareXml(array $data): string
    {
        return Helper::ArrayToXml($data, '<GVPSRequest/>');
    }

    public function sendData($data)
    {
        $httpResponse = $this->httpClient->request(
            'POST',
            $this->getTestMode() ? $this->test_endpoint : $this->prod_endpoint,
            [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/xml',
            ],
            $this->prepareXml($data)
        );

        return new RefundResponse($this, $httpResponse);
    }
}

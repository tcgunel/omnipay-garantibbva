<?php

namespace Omnipay\Garantibbva\Message;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Garantibbva\Constants\TransactionTypes;
use Omnipay\Garantibbva\Helpers\Helper;
use Omnipay\Garantibbva\Traits\PurchaseGettersSetters;

/**
 * İptal (cancel/void) — invalidates a sale on the day it was made.
 * (https://dev.garantibbva.com.tr/sanalpos-iptal-iptal-3dsiz)
 *
 * The sale is addressed by its OrderID plus the OriginalRetrefNum assigned to
 * it; from the following day the sale can only be refunded, never cancelled.
 */
class VoidRequest extends AbstractRequest
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
            'original_retref_num',
            'amount',
            'currency',
            'transactionId',
            'testMode',
            'client_ip',
            'card',
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
                'EmailAddress' => $this->getCard()->getEmail(),
            ],
            'Order' => [
                'OrderID' => $this->getTransactionId(),
                'GroupID' => '',
            ],
            'Transaction' => [
                'Type' => TransactionTypes::VOID,
                'Amount' => $this->getAmountInteger(),
                'CurrencyCode' => $this->getCurrencyNumeric(),
                'OriginalRetrefNum' => $this->getOriginalRetrefNum(),
                // The docs' void sample sends an empty CardholderPresentCode
                // and MotoInd=N for e-commerce (the field spec allows Y/N).
                'CardholderPresentCode' => '',
                'MotoInd' => 'N',
            ],
        ];
    }

    private function hash(): string
    {
        $hashPasswordData = [
            $this->getRefundUserPassword(),
            str_pad((string) (int) $this->getTerminalId(), 9, '0', STR_PAD_LEFT),
        ];

        $hashedPassword = strtoupper(sha1(implode('', $hashPasswordData)));

        // For PROVRFN operations the hash covers OrderID + TerminalID + Amount
        // + CurrencyCode (no card number — none is sent) + the hashed password.
        // OriginalRetrefNum is NOT part of the hash (verified against the
        // docs' own sample values).
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

        return new VoidResponse($this, $httpResponse);
    }
}

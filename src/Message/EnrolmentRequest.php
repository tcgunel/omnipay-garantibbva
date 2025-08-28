<?php

namespace Omnipay\Garantibbva\Message;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Item;
use Omnipay\Common\Message\AbstractRequest;
use Omnipay\Garantibbva\Constants\Secure3DSecurityLevel;
use Omnipay\Garantibbva\Constants\TransactionTypes;
use Omnipay\Garantibbva\Traits\PurchaseGettersSetters;

class EnrolmentRequest extends AbstractRequest
{
    use PurchaseGettersSetters;

    protected $test_endpoint = 'https://sanalposprovtest.garantibbva.com.tr/servlet/gt3dengine';

    protected $prod_endpoint = 'https://sanalposprov.garanti.com.tr/servlet/gt3dengine';

    /**
     * @throws InvalidRequestException
     * @throws InvalidCreditCardException
     */
    public function getData()
    {
        $this->validate(
            'api_version',
            'terminal_user_id',
            'terminal_merchant_id',
            'terminal_id',
            'prov_user_password',
            'amount',
            'currency',
            'installment',
            'transactionId',
            'returnUrl',
            'cancelUrl',
            'testMode',
            'client_ip',
        );

        $this->getCard()->validate();

        $data = [
            'mode' => $this->getTestMode() ? 'TEST' : 'PROD',

            'apiversion'         => $this->getApiVersion(),
            'terminalprovuserid' => 'PROVAUT',
            // 3d örnek işlemde GARANTI kullanılmış? her müşteriye özel mi bilinmiyor.
            'terminaluserid'     => $this->getTerminalUserId(),
            'terminalmerchantid' => $this->getTerminalMerchantId(),
            'terminalid'         => $this->getTerminalId(),

            'txntype'             => TransactionTypes::SALES,
            'txncurrencycode'     => $this->getCurrencyNumeric(),
            'txninstallmentcount' => $this->getInstallment(),
            'txnamount'           => $this->getAmountInteger(),
            'orderid'             => $this->getTransactionId(),

            'successurl' => $this->getReturnUrl(),
            'errorurl'   => $this->getCancelUrl(),

            'customeremailaddress' => $this->getCard()->getEmail(),
            'customeripaddress'    => $this->getClientIp(),
            'companyname'          => $this->getCompanyName(),
            'lang'                 => $this->getLang(),
            'txntimestamp'         => date('h:i:sa'),
            'refreshtime'          => 1,

            'secure3dhash' => $this->hash(),

            'secure3dsecuritylevel' => $this->getSecure3DSecurityLevel() ?? Secure3DSecurityLevel::PAY,

            'orderaddresscount'        => 2,
            'orderaddresstype1'        => 'B',
            'orderaddresscountry1'     => $this->getCard()->getBillingCountry() ?? '',
            'orderaddresscity1'        => $this->getCard()->getBillingCity() ?? '',
            'orderaddressdistrict1'    => $this->getCard()->getBillingState() ?? '',
            'orderaddresscompany1'     => $this->getCard()->getBillingCompany() ?? '',
            'orderaddressfaxnumber1'   => $this->getCard()->getBillingFax() ?? '',
            'orderaddressgsmnumber1'   => $this->getCard()->getBillingPhone() ?? '',
            'orderaddresslastname1'    => $this->getCard()->getBillingLastName() ?? '',
            'orderaddressname1'        => $this->getCard()->getBillingFirstName() ?? '',
            'orderaddressphonenumber1' => $this->getCard()->getBillingPhone() ?? '',
            'orderaddresspostalcode1'  => $this->getCard()->getBillingPostcode() ?? '',
            'orderaddresstext1'        => implode(' ', [$this->getCard()->getBillingAddress1(), $this->getCard()->getBillingAddress2()]) ?? '',

            'orderaddresstype2'        => 'S',
            'orderaddresscity2'        => $this->getCard()->getShippingCity() ?? '',
            'orderaddresscompany2'     => $this->getCard()->getShippingCompany() ?? '',
            'orderaddresscountry2'     => $this->getCard()->getShippingCountry() ?? '',
            'orderaddressdistrict2'    => $this->getCard()->getShippingState() ?? '',
            'orderaddressfaxnumber2'   => $this->getCard()->getShippingFax() ?? '',
            'orderaddressgsmnumber2'   => $this->getCard()->getShippingPhone() ?? '',
            'orderaddresslastname2'    => $this->getCard()->getShippingLastName() ?? '',
            'orderaddressname2'        => $this->getCard()->getShippingFirstName() ?? '',
            'orderaddressphonenumber2' => $this->getCard()->getShippingPhone() ?? '',
            'orderaddresspostalcode2'  => $this->getCard()->getShippingPostcode() ?? '',
            'orderaddresstext2'        => implode(' ', [$this->getCard()->getShippingAddress1(), $this->getCard()->getShippingAddress2()]) ?? '',

            'orderitemcount' => count($this->getItems()),

            'cardholdername'      => $this->getCard()->getName(),
            'cardnumber'          => $this->getCard()->getNumber(),
            'cardexpiredatemonth' => str_pad($this->getCard()->getExpiryMonth(), 2, '0', STR_PAD_LEFT),
            'cardexpiredateyear'  => substr($this->getCard()->getExpiryYear(), -2),
            'cardcvv2'            => $this->getCard()->getCvv(),
        ];

        if ((int)$data['txninstallmentcount'] === 1){
            unset($data['txninstallmentcount']);
        }

        /**
         * @var Item $item
         */
        foreach ($this->getItems() as $key => $item) {

            $index = $key + 1;
            $data["ordercommentnumber{$index}"] = $index;
            $data["orderproductid{$index}"] = $item->getDescription();
            $data["orderproductcode{$index}"] = $item->getDescription();
            $data["orderquantity{$index}"] = $item->getQuantity();
            $data["ordertotalamount{$index}"] = ($item->getPrice() * 100) * $item->getQuantity();
            $data["orderdescription{$index}"] = substr($item->getName(), 0, 20);

        }

        return $data;
    }

    private function hash(): string
    {
        $hashPasswordData = [
            $this->getProvUserPassword(),
            str_pad((int)$this->getTerminalId(), 9, 0, STR_PAD_LEFT)
        ];

        $hashedPassword = strtoupper(sha1(implode('', $hashPasswordData)));

        $installment = (int)$this->getInstallment() === 1 ? '' : $this->getInstallment();

        $hashedDataArr = [
            $this->getTerminalId(), $this->getTransactionId(), $this->getAmountInteger(), $this->getCurrencyNumeric(),
            $this->getReturnUrl(), $this->getCancelUrl(),
            TransactionTypes::SALES, $installment,
            $this->getStoreKey(), $hashedPassword
        ];

        return strtoupper(hash('sha512', implode('', $hashedDataArr)));
    }

    public function sendData($data)
    {
        return $this->createResponse($data);
    }

    protected function createResponse($data): EnrolmentResponse
    {
        return $this->response = new EnrolmentResponse($this, $data);
    }

    public function getEndpoint()
    {
        return $this->getTestMode() ? $this->test_endpoint : $this->prod_endpoint;
    }
}

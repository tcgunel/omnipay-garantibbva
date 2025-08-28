<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Omnipay\Common\CreditCard;
use Omnipay\Omnipay;

/** @var \Omnipay\Garantibbva\Gateway $gateway */
$gateway = Omnipay::create('Garantibbva');

$gateway
    ->setTestMode(true)
    ->setTerminalUserId('GARANTI') // default GARANTI.
    ->setTerminalMerchantId('7000679')
    ->setTerminalId('30691297')
    ->setStoreKey('12345678')
    ->setProvUserPassword('123qweASD/')
    ->setClientIp('127.0.0.1');

$creditCard = (new CreditCard())
    // CREDIT CARD INFO.
    ->setEmail('omnipay-test-email@gmail.com');

/** @var \Omnipay\Garantibbva\Message\FetchTransactionRequest $fetch_request */
$fetch_request = $gateway->fetchTransaction();

$fetch_request
    ->setCurrency('TRY')
    ->setAmount('3.00')
    ->setTransactionId('OMNIPAY-TEST-003')
    ->setCard($creditCard);

/** @var \Omnipay\Garantibbva\Message\FetchTransactionResponse $fetch_response */
$fetch_response = $fetch_request->send();

if ($fetch_response->isSuccessful()) {
    echo '<h1>BAŞARILI İŞLEM</h1>';
    var_dump($fetch_response->getData());
} else {
    echo $fetch_response->getMessage();
}

<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Omnipay\Common\ItemBag;
use Omnipay\Garantibbva\Constants\Secure3DSecurityLevel;
use Omnipay\Common\CreditCard;
use Omnipay\Garantibbva\Message\EnrolmentRequest;
use Omnipay\Garantibbva\Message\EnrolmentResponse;
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
    ->setClientIp('127.0.0.1')
    ->setLang('en')
    ->setCompanyName('tcgunel/omnipay-garantibbva');

$creditCard = (new CreditCard())
    // CREDIT CARD INFO.
    ->setEmail('omnipay-test-email@gmail.com')
    ->setFirstName('Omnipay')
    ->setLastName('Test')
    ->setNumber('5549600711217012')
    ->setExpiryMonth('08')
    ->setExpiryYear('2029')
    ->setCvv('819')

    // BILLING INFO.
    ->setBillingCountry('Turkey')
    ->setBillingCity('Izmir')
    ->setBillingState('Buca')

    ->setBillingAddress1('LINE 1 BILLING ADDRESS LINE 1')
    ->setBillingAddress2('LINE 2 BILLING ADDRESS LINE 2')

    ->setBillingPostcode('')
    ->setBillingFax('')
    ->setBillingPhone('+905554443322')

    ->setBillingName('BILLING NAME')
    ->setBillingCompany('BILLING COMPANY')

    // SHIPPING INFO.
    ->setShippingCountry('Turkey')
    ->setShippingCity('Izmir')
    ->setShippingState('Alsancak')

    ->setShippingAddress1('LINE 1 SHIPPING ADDRESS LINE 1')
    ->setShippingAddress2('LINE 2 SHIPPING ADDRESS LINE 2')

    ->setShippingPostcode('')
    ->setShippingFax('')
    ->setShippingPhone('+905554443322')

    ->setShippingName('SHIPPING NAME')
    ->setShippingCompany('SHIPPING COMPANY');

$item_bag = new ItemBag();
$item_bag->add([
    'name'        => 'Item 1',
    'description' => uniqid(),
    'quantity'    => 1,
    'price'       => '1000.00',
]);
$item_bag->add([
    'name'        => 'Item 2',
    'description' => uniqid(),
    'quantity'    => 2,
    'price'       => '1100.00',
]);

/** @var EnrolmentRequest $enrollment */
$enrollment = $gateway->enrollment();

$enrollment
    ->setReturnUrl('http://kolaysiparis.test/omnipay-garantibbva/examples/payment-threed-return.php')
    ->setCancelUrl('http://kolaysiparis.test/omnipay-garantibbva/examples/payment-threed-cancel.php')
    ->setSecure3DSecurityLevel(Secure3DSecurityLevel::PAY) // default 3D_PAY.
    ->setCurrency('TRY')
    ->setInstallment(6)
    ->setAmount('3200.00')
    ->setTransactionId('OMNIPAY-TEST-003')
    ->setCard($creditCard)
    ->setItems($item_bag);

/** @var EnrolmentResponse $enrollment_response */
$enrollment_response = $enrollment->send();

if ($enrollment_response->isRedirect()) {
    $enrollment_response->redirect();
} else {
    echo $enrollment_response->getMessage();
}

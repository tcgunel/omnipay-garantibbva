<?php

namespace Omnipay\Garantibbva\Tests\Feature;

use Omnipay\Common\Exception\InvalidCreditCardException;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Garantibbva\Message\EnrolmentRequest;
use Omnipay\Garantibbva\Message\EnrolmentResponse;
use Omnipay\Garantibbva\Tests\TestCase;

class EnrolmentTest extends TestCase
{
	/**
	 * @throws InvalidRequestException
	 * @throws InvalidCreditCardException
	 * @throws \JsonException
	 */
	public function test_enrolment_request()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new EnrolmentRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$data = $request->getData();

		$this->assertIsArray($data);

		// Verify mode
		$this->assertEquals('TEST', $data['mode']);

		// Verify terminal info
		$this->assertEquals(512, $data['apiversion']);
		$this->assertEquals('PROVAUT', $data['terminalprovuserid']);
		$this->assertEquals('GARANTI', $data['terminaluserid']);
		$this->assertEquals('7000679', $data['terminalmerchantid']);
		$this->assertEquals('30691298', $data['terminalid']);

		// Verify transaction info
		$this->assertEquals('sales', $data['txntype']);
		$this->assertEquals('949', $data['txncurrencycode']);
		$this->assertEquals(1234, $data['txnamount']);
		$this->assertEquals('TEST-ORDER-001', $data['orderid']);

		// Verify installment count is removed when == 1
		$this->assertArrayNotHasKey('txninstallmentcount', $data);

		// Verify URLs
		$this->assertEquals('https://example.com/success', $data['successurl']);
		$this->assertEquals('https://example.com/failure', $data['errorurl']);

		// Verify customer info
		$this->assertEquals('test@example.com', $data['customeremailaddress']);
		$this->assertEquals('127.0.0.1', $data['customeripaddress']);
		$this->assertEquals('Test Company', $data['companyname']);
		$this->assertEquals('tr', $data['lang']);

		// Verify 3D secure hash (SHA512)
		$this->assertEquals(
			'23B6EB646E0C7C9924E8147FAE4652F3F120ECBB7DA542421CDB9BF7DCEB0E4504DD8C3B879CCDB9599637AB3738416B7614EA81FC7796684CD33CD029DE67CC',
			$data['secure3dhash']
		);

		// Verify 3D security level
		$this->assertEquals('3D_PAY', $data['secure3dsecuritylevel']);

		// Verify billing address
		$this->assertEquals('B', $data['orderaddresstype1']);
		$this->assertEquals('TR', $data['orderaddresscountry1']);
		$this->assertEquals('Istanbul', $data['orderaddresscity1']);
		$this->assertEquals('IST', $data['orderaddressdistrict1']);
		$this->assertEquals('Test Billing Co', $data['orderaddresscompany1']);
		$this->assertEquals('2121234567', $data['orderaddressfaxnumber1']);
		$this->assertEquals('5554443322', $data['orderaddressgsmnumber1']);
		$this->assertEquals('User', $data['orderaddresslastname1']);
		$this->assertEquals('Example', $data['orderaddressname1']);
		$this->assertEquals('5554443322', $data['orderaddressphonenumber1']);
		$this->assertEquals('34000', $data['orderaddresspostalcode1']);
		$this->assertEquals('123 Billing St Billsville', $data['orderaddresstext1']);

		// Verify shipping address
		$this->assertEquals('S', $data['orderaddresstype2']);
		$this->assertEquals('Ankara', $data['orderaddresscity2']);
		$this->assertEquals('Test Shipping Co', $data['orderaddresscompany2']);
		$this->assertEquals('TR', $data['orderaddresscountry2']);
		$this->assertEquals('ANK', $data['orderaddressdistrict2']);
		$this->assertEquals('3121234567', $data['orderaddressfaxnumber2']);
		$this->assertEquals('5559998877', $data['orderaddressgsmnumber2']);
		$this->assertEquals('User', $data['orderaddresslastname2']);
		$this->assertEquals('Example', $data['orderaddressname2']);
		$this->assertEquals('5559998877', $data['orderaddressphonenumber2']);
		$this->assertEquals('06000', $data['orderaddresspostalcode2']);
		$this->assertEquals('456 Shipping St Shipsville', $data['orderaddresstext2']);

		// Verify card info
		$this->assertEquals('Example User', $data['cardholdername']);
		$this->assertEquals('4111111111111111', $data['cardnumber']);
		$this->assertEquals('12', $data['cardexpiredatemonth']);
		$this->assertEquals('99', $data['cardexpiredateyear']);
		$this->assertEquals('000', $data['cardcvv2']);

		// Verify item info
		$this->assertEquals(1, $data['orderitemcount']);
		$this->assertEquals(1, $data['ordercommentnumber1']);
		$this->assertEquals('PROD001', $data['orderproductid1']);
		$this->assertEquals('PROD001', $data['orderproductcode1']);
		$this->assertEquals(1, $data['orderquantity1']);
		$this->assertEquals(1234, $data['ordertotalamount1']);
		$this->assertEquals('Test Product', $data['orderdescription1']);
	}

	/**
	 * @throws InvalidRequestException
	 * @throws InvalidCreditCardException
	 * @throws \JsonException
	 */
	public function test_enrolment_request_with_installment()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequestWithInstallment.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new EnrolmentRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$data = $request->getData();

		// Verify installment count is present when > 1
		$this->assertArrayHasKey('txninstallmentcount', $data);
		$this->assertEquals(3, $data['txninstallmentcount']);

		// Verify hash is different when installment > 1 (installment value is included)
		$this->assertEquals(
			'2EB947F1F7DA70D57F717AFAB243271770329892E7ABAD75F0E6B476656087F2DC09B0B2265C455EB4AEBC6FB6B75C6A66299E93CC3734596495E81FB859B54C',
			$data['secure3dhash']
		);
	}

	public function test_enrolment_request_validation_error()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest-ValidationError.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new EnrolmentRequest($this->getHttpClient(), $this->getHttpRequest());

		$request->initialize($options);

		$this->expectException(InvalidRequestException::class);

		$request->getData();
	}

	/**
	 * @throws InvalidRequestException
	 * @throws InvalidCreditCardException
	 * @throws \JsonException
	 */
	public function test_enrolment_response()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$request = new EnrolmentRequest($this->getHttpClient(), $this->getHttpRequest());

		/** @var EnrolmentResponse $response */
		$response = $request->initialize($options)->send();

		$this->assertTrue($response->isSuccessful());

		$this->assertTrue($response->isRedirect());

		$this->assertEquals('POST', $response->getRedirectMethod());

		$this->assertEquals(
			'https://sanalposprovtest.garantibbva.com.tr/servlet/gt3dengine',
			$response->getRedirectUrl()
		);

		$redirectData = $response->getRedirectData();

		$this->assertIsArray($redirectData);

		$this->assertEquals((array) $request->getData(), $redirectData);
	}

	/**
	 * @throws InvalidRequestException
	 * @throws InvalidCreditCardException
	 * @throws \JsonException
	 */
	public function test_enrolment_response_prod_endpoint()
	{
		$options = file_get_contents(__DIR__ . "/../Mock/EnrolmentRequest.json");

		$options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

		$options['testMode'] = false;

		$request = new EnrolmentRequest($this->getHttpClient(), $this->getHttpRequest());

		/** @var EnrolmentResponse $response */
		$response = $request->initialize($options)->send();

		$this->assertEquals(
			'https://sanalposprov.garanti.com.tr/servlet/gt3dengine',
			$response->getRedirectUrl()
		);
	}

	public function test_enrolment_gateway_method()
	{
		$request = $this->gateway->enrolment([
			'terminal_merchant_id' => '7000679',
			'terminal_id'          => '30691298',
			'prov_user_password'   => 'testPassword123',
		]);

		$this->assertInstanceOf(EnrolmentRequest::class, $request);
	}
}

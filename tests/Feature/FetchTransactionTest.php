<?php

namespace Omnipay\Garantibbva\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Garantibbva\Message\FetchTransactionRequest;
use Omnipay\Garantibbva\Message\FetchTransactionResponse;
use Omnipay\Garantibbva\Tests\TestCase;

class FetchTransactionTest extends TestCase
{
    /**
     * @throws InvalidRequestException
     * @throws \JsonException
     */
    public function test_fetch_transaction_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/FetchTransactionRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new FetchTransactionRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        $this->assertIsArray($data);

        // Verify mode
        $this->assertEquals('TEST', $data['Mode']);

        // Verify version
        $this->assertEquals(512, $data['Version']);

        // Verify terminal info
        $this->assertEquals('PROVAUT', $data['Terminal']['ProvUserID']);
        $this->assertEquals('GARANTI', $data['Terminal']['UserID']);
        $this->assertEquals('30691298', $data['Terminal']['ID']);
        $this->assertEquals('7000679', $data['Terminal']['MerchantID']);

        // Verify hash (SHA512)
        $this->assertEquals(
            'A27AF49D89717C2BE7A588B71303461B437DF0EF269F0AD07FCD6E3B484C0220509B9A457AA0FF6D2C9A20A959CBCB76904AB9E09152F68F01112EA847A29AB8',
            $data['Terminal']['HashData']
        );

        // Verify customer info
        $this->assertEquals('127.0.0.1', $data['Customer']['IPAddress']);
        $this->assertEquals('test@example.com', $data['Customer']['EmailAddress']);

        // Verify order info
        $this->assertEquals('TEST-ORDER-001', $data['Order']['OrderID']);
        $this->assertEquals('', $data['Order']['GroupID']);

        // Verify transaction info
        $this->assertEquals('orderinq', $data['Transaction']['Type']);
        $this->assertEquals(1, $data['Transaction']['ListPageNum']);
        $this->assertEquals(1234, $data['Transaction']['Amount']);
        $this->assertEquals('949', $data['Transaction']['CurrencyCode']);
        $this->assertEquals('0', $data['Transaction']['CardholderPresentCode']); // secure=false
        $this->assertEquals('N', $data['Transaction']['MotoInd']);
    }

    /**
     * @throws InvalidRequestException
     * @throws \JsonException
     */
    public function test_fetch_transaction_request_with_secure()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/FetchTransactionRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['secure'] = true;

        $request = new FetchTransactionRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        // When secure=true, CardholderPresentCode should be '13'
        $this->assertEquals('13', $data['Transaction']['CardholderPresentCode']);
    }

    public function test_fetch_transaction_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/FetchTransactionRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new FetchTransactionRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_fetch_transaction_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('FetchTransactionResponseSuccess.txt');

        $response = new FetchTransactionResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());

        $data = $response->getData();

        $this->assertInstanceOf(\stdClass::class, $data);

        $this->assertEquals('00', $data->Order->OrderInqResult->Code);
        $this->assertEquals('APPROVED', $data->Order->OrderInqResult->Status);
        $this->assertEquals('TEST-ORDER-001', $data->Order->OrderID);

        $this->assertEquals('00', $data->Transaction->Response->Code);
        $this->assertEquals('Approved', $data->Transaction->Response->Message);
        $this->assertEquals('335709614892', $data->Transaction->RetrefNum);
        $this->assertEquals('304919', $data->Transaction->AuthCode);
        $this->assertEquals('411111******1111', $data->Transaction->CardNumberMasked);

        // getMessage should be empty on success
        $this->assertEquals('', $response->getMessage());
    }

    public function test_fetch_transaction_response_error()
    {
        $httpResponse = $this->getMockHttpResponse('FetchTransactionResponseError.txt');

        $response = new FetchTransactionResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());

        $data = $response->getData();

        $this->assertInstanceOf(\stdClass::class, $data);

        $this->assertEquals('99', $data->Order->OrderInqResult->Code);
        $this->assertEquals('DECLINED', $data->Order->OrderInqResult->Status);

        // getMessage should contain error messages
        $message = $response->getMessage();
        $this->assertStringContainsString('Order not found', $message);
        $this->assertStringContainsString('Order does not exist', $message);
        $this->assertStringContainsString('System error: record not found', $message);
    }

    public function test_fetch_transaction_sends_http_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/FetchTransactionRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $this->setMockHttpResponse('FetchTransactionResponseSuccess.txt');

        $response = $this->gateway->fetchTransaction($options)->send();

        $this->assertTrue($response->isSuccessful());

        // Verify the HTTP request was actually sent
        $requests = $this->getMockedRequests();
        $this->assertCount(1, $requests);

        $httpRequest = $requests[0];
        $this->assertEquals('POST', $httpRequest->getMethod());
        $this->assertStringContainsString(
            'sanalposprovtest.garantibbva.com.tr/VPServlet',
            (string) $httpRequest->getUri()
        );

        // Verify the body is XML
        $body = (string) $httpRequest->getBody();
        $this->assertStringContainsString('GVPSRequest', $body);
        $this->assertStringContainsString('<Mode>TEST</Mode>', $body);
        $this->assertStringContainsString('<OrderID>TEST-ORDER-001</OrderID>', $body);
        $this->assertStringContainsString('<Type>orderinq</Type>', $body);

        // Verify content type header
        $this->assertEquals(
            'application/x-www-form-urlencoded',
            $httpRequest->getHeaderLine('Content-Type')
        );
    }

    public function test_fetch_transaction_prod_endpoint()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/FetchTransactionRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['testMode'] = false;

        $this->setMockHttpResponse('FetchTransactionResponseSuccess.txt');

        $response = $this->gateway->fetchTransaction($options)->send();

        $requests = $this->getMockedRequests();
        $httpRequest = $requests[0];

        $this->assertStringContainsString(
            'sanalposprov.garanti.com.tr/VPServlet',
            (string) $httpRequest->getUri()
        );
    }

    public function test_fetch_transaction_gateway_method()
    {
        $request = $this->gateway->fetchTransaction([
            'terminal_merchant_id' => '7000679',
            'terminal_id' => '30691298',
            'prov_user_password' => 'testPassword123',
        ]);

        $this->assertInstanceOf(FetchTransactionRequest::class, $request);
    }
}

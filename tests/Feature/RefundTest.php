<?php

namespace Omnipay\Garantibbva\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Garantibbva\Message\RefundRequest;
use Omnipay\Garantibbva\Message\RefundResponse;
use Omnipay\Garantibbva\Tests\TestCase;

/**
 * İade (refund) against the documented PROVRFN flow
 * (https://dev.garantibbva.com.tr/sanalpos-iade-iade-3dsiz).
 *
 * The refund is addressed by the sale's OrderID; the hash is
 * SHA512(OrderID + TerminalID + Amount + CurrencyCode + SHA1(password +
 * padded terminal)), pinned below to the docs' own sample values.
 */
class RefundTest extends TestCase
{
    /**
     * @throws InvalidRequestException
     * @throws \JsonException
     */
    public function test_refund_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/RefundRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        $this->assertIsArray($data);

        // Verify mode
        $this->assertEquals('TEST', $data['Mode']);

        // Verify version
        $this->assertEquals(512, $data['Version']);

        // Verify terminal info — refunds run as the PROVRFN user
        $this->assertEquals('PROVRFN', $data['Terminal']['ProvUserID']);
        $this->assertEquals('PROVAUT', $data['Terminal']['UserID']);
        $this->assertEquals('30691297', $data['Terminal']['ID']);
        $this->assertEquals('7000679', $data['Terminal']['MerchantID']);

        // Verify hash (SHA512) against the docs' own iade sample values:
        // password 123qweASD/, terminal 30691297, order ec2e065b…, 100.00 TRY.
        $this->assertEquals(
            'F252824795D1128797922A61320819721E5D178BDF6401C09EF742DF5833A88559978CCC164B8161FD4424DBB77D80BAC470991A549E774A8F490C47EC717B38',
            $data['Terminal']['HashData']
        );

        // Verify customer info
        $this->assertEquals('192.168.0.1', $data['Customer']['IPAddress']);
        $this->assertEquals('eticaret@garanti.com.tr', $data['Customer']['EmailAddress']);

        // Verify order info
        $this->assertEquals('ec2e065b7e32496a8986a18957a7e60e', $data['Order']['OrderID']);
        $this->assertEquals('', $data['Order']['GroupID']);

        // Verify transaction info
        $this->assertEquals('refund', $data['Transaction']['Type']);
        $this->assertEquals(10000, $data['Transaction']['Amount']);
        $this->assertEquals('949', $data['Transaction']['CurrencyCode']);
        $this->assertEquals('0', $data['Transaction']['CardholderPresentCode']);
        $this->assertEquals('N', $data['Transaction']['MotoInd']);
    }

    public function test_refund_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/RefundRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new RefundRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_refund_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('RefundResponseSuccess.txt');

        $response = new RefundResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());

        // The refund's own RetrefNum comes back for reconciliation.
        $this->assertEquals('210707283286', $response->getTransactionId());
        $this->assertEquals('00', $response->getCode());
        $this->assertEquals('', $response->getMessage());

        $data = $response->getData();

        $this->assertInstanceOf(\stdClass::class, $data);

        $this->assertEquals('00', $data->Transaction->Response->Code);
        $this->assertEquals('Approved', $data->Transaction->Response->Message);
        $this->assertEquals('ec2e065b7e32496a8986a18957a7e60e', $data->Order->OrderID);
    }

    public function test_refund_response_error()
    {
        $httpResponse = $this->getMockHttpResponse('RefundResponseError.txt');

        $response = new RefundResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());

        $this->assertEquals('3743', $response->getCode());
        $this->assertStringContainsString('Refund transaction not found', $response->getMessage());
    }

    public function test_refund_sends_http_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/RefundRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $this->setMockHttpResponse('RefundResponseSuccess.txt');

        $response = $this->gateway->refund($options)->send();

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
        $this->assertStringContainsString('<OrderID>ec2e065b7e32496a8986a18957a7e60e</OrderID>', $body);
        $this->assertStringContainsString('<Type>refund</Type>', $body);
        $this->assertStringContainsString('<Amount>10000</Amount>', $body);
        $this->assertStringContainsString('<CurrencyCode>949</CurrencyCode>', $body);
        $this->assertStringContainsString('<CardholderPresentCode>0</CardholderPresentCode>', $body);
        $this->assertStringContainsString('<MotoInd>N</MotoInd>', $body);

        // Verify content type header
        $this->assertEquals(
            'application/x-www-form-urlencoded',
            $httpRequest->getHeaderLine('Content-Type')
        );
    }

    public function test_refund_prod_endpoint()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/RefundRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['testMode'] = false;

        $this->setMockHttpResponse('RefundResponseSuccess.txt');

        $response = $this->gateway->refund($options)->send();

        $requests = $this->getMockedRequests();
        $httpRequest = $requests[0];

        $this->assertStringContainsString(
            'sanalposprov.garanti.com.tr/VPServlet',
            (string) $httpRequest->getUri()
        );
    }

    public function test_refund_gateway_method()
    {
        $request = $this->gateway->refund([
            'terminal_merchant_id' => '7000679',
            'terminal_id' => '30691298',
            'refund_user_password' => 'testPassword123',
        ]);

        $this->assertInstanceOf(RefundRequest::class, $request);
    }
}

<?php

namespace Omnipay\Garantibbva\Tests\Feature;

use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Garantibbva\Message\VoidRequest;
use Omnipay\Garantibbva\Message\VoidResponse;
use Omnipay\Garantibbva\Tests\TestCase;

/**
 * İptal (void/cancel) against the documented PROVRFN flow
 * (https://dev.garantibbva.com.tr/sanalpos-iptal-iptal-3dsiz).
 *
 * A cancel is only possible on the day of the sale and invalidates the whole
 * sale; the request carries the sale's OriginalRetrefNum (not part of the
 * hash — pinned below to the docs' own sample values).
 */
class VoidTest extends TestCase
{
    /**
     * @throws InvalidRequestException
     * @throws \JsonException
     */
    public function test_void_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/VoidRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $data = $request->getData();

        $this->assertIsArray($data);

        // Verify mode
        $this->assertEquals('TEST', $data['Mode']);

        // Verify version
        $this->assertEquals(512, $data['Version']);

        // Verify terminal info — cancels run as the PROVRFN user
        $this->assertEquals('PROVRFN', $data['Terminal']['ProvUserID']);
        $this->assertEquals('PROVAUT', $data['Terminal']['UserID']);
        $this->assertEquals('30691297', $data['Terminal']['ID']);
        $this->assertEquals('7000679', $data['Terminal']['MerchantID']);

        // Verify hash (SHA512) against the docs' own iptal sample values:
        // password 123qweASD/, terminal 30691297, order 981fb46e…, 100.00 TRY.
        $this->assertEquals(
            'F1C66A60A57F9DB0657A52708BFDD84BBD17E0CC5B00A0E5DEA2417EAD76058A9F2B97DF232D9918CAC917EDA4C99E8FA971EBC0395CD37879AD689689E2C9EF',
            $data['Terminal']['HashData']
        );

        // Verify customer info
        $this->assertEquals('192.168.0.1', $data['Customer']['IPAddress']);
        $this->assertEquals('eticaret@garanti.com.tr', $data['Customer']['EmailAddress']);

        // Verify order info
        $this->assertEquals('981fb46e0d464dab9de9f392f090a892', $data['Order']['OrderID']);
        $this->assertEquals('', $data['Order']['GroupID']);

        // Verify transaction info
        $this->assertEquals('void', $data['Transaction']['Type']);
        $this->assertEquals(10000, $data['Transaction']['Amount']);
        $this->assertEquals('949', $data['Transaction']['CurrencyCode']);
        $this->assertEquals('210707283285', $data['Transaction']['OriginalRetrefNum']);
        $this->assertEquals('', $data['Transaction']['CardholderPresentCode']);
        $this->assertEquals('N', $data['Transaction']['MotoInd']);
    }

    public function test_void_request_validation_error()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/VoidRequest-ValidationError.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $request = new VoidRequest($this->getHttpClient(), $this->getHttpRequest());

        $request->initialize($options);

        $this->expectException(InvalidRequestException::class);

        $request->getData();
    }

    public function test_void_response_success()
    {
        $httpResponse = $this->getMockHttpResponse('VoidResponseSuccess.txt');

        $response = new VoidResponse($this->getMockRequest(), $httpResponse);

        $this->assertTrue($response->isSuccessful());

        $this->assertEquals('210707283287', $response->getTransactionId());
        $this->assertEquals('00', $response->getCode());
        $this->assertEquals('', $response->getMessage());

        $data = $response->getData();

        $this->assertInstanceOf(\stdClass::class, $data);

        $this->assertEquals('00', $data->Transaction->Response->Code);
        $this->assertEquals('Approved', $data->Transaction->Response->Message);
        $this->assertEquals('981fb46e0d464dab9de9f392f090a892', $data->Order->OrderID);
    }

    public function test_void_response_error()
    {
        $httpResponse = $this->getMockHttpResponse('VoidResponseError.txt');

        $response = new VoidResponse($this->getMockRequest(), $httpResponse);

        $this->assertFalse($response->isSuccessful());

        $this->assertEquals('3744', $response->getCode());
        $this->assertStringContainsString('Void transaction not found', $response->getMessage());
    }

    public function test_void_sends_http_request()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/VoidRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $this->setMockHttpResponse('VoidResponseSuccess.txt');

        $response = $this->gateway->void($options)->send();

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
        $this->assertStringContainsString('<OrderID>981fb46e0d464dab9de9f392f090a892</OrderID>', $body);
        $this->assertStringContainsString('<Type>void</Type>', $body);
        $this->assertStringContainsString('<Amount>10000</Amount>', $body);
        $this->assertStringContainsString('<CurrencyCode>949</CurrencyCode>', $body);
        $this->assertStringContainsString('<OriginalRetrefNum>210707283285</OriginalRetrefNum>', $body);
        $this->assertStringContainsString('<MotoInd>N</MotoInd>', $body);

        // Verify content type header
        $this->assertEquals(
            'application/x-www-form-urlencoded',
            $httpRequest->getHeaderLine('Content-Type')
        );
    }

    public function test_void_prod_endpoint()
    {
        $options = file_get_contents(__DIR__ . '/../Mock/VoidRequest.json');

        $options = json_decode($options, true, 512, JSON_THROW_ON_ERROR);

        $options['testMode'] = false;

        $this->setMockHttpResponse('VoidResponseSuccess.txt');

        $response = $this->gateway->void($options)->send();

        $requests = $this->getMockedRequests();
        $httpRequest = $requests[0];

        $this->assertStringContainsString(
            'sanalposprov.garanti.com.tr/VPServlet',
            (string) $httpRequest->getUri()
        );
    }

    public function test_void_gateway_method()
    {
        $request = $this->gateway->void([
            'terminal_merchant_id' => '7000679',
            'terminal_id' => '30691298',
            'refund_user_password' => 'testPassword123',
        ]);

        $this->assertInstanceOf(VoidRequest::class, $request);
    }
}

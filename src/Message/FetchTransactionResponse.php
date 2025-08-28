<?php

namespace Omnipay\Garantibbva\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Garantibbva\Helpers\Helper;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class FetchTransactionResponse extends AbstractResponse
{
    protected $response;

    protected $request;

    public function __construct(RequestInterface $request, $data)
    {
        parent::__construct($request, $data);

        $this->request = $request;

        $this->response = $data;

        if ($this->response instanceof ResponseInterface) {
            $this->response = Helper::XMLStringToObject($this->response->getBody()->getContents());
        }
    }

    public function isSuccessful(): bool
    {
        return $this->response->Order?->OrderInqResult?->Code === '00' && $this->response->Order?->OrderInqResult?->Status === 'APPROVED';
    }

    public function getMessage(): string
    {
        return implode('', [
            $this->response->Order?->OrderInqResult?->SysErrMsg,
            isset($this->response->Transaction?->Response?->ResultDetail) ? $this->response->Transaction?->Response?->ResultDetail?->ErrorMsg : $this->response->Transaction?->Response?->ErrorMsg,
            isset($this->response->Transaction?->Response?->ResultDetail) ? $this->response->Transaction?->Response?->ResultDetail?->SysErrMsg :  $this->response->Transaction?->Response?->SysErrMsg,
        ]);
    }

    public function getData(): stdClass
    {
        return $this->response;
    }
}

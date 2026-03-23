<?php

namespace Omnipay\Garantibbva\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Garantibbva\Helpers\Helper;
use Psr\Http\Message\ResponseInterface;
use stdClass;

class RefundResponse extends AbstractResponse
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
        return ($this->response->Transaction?->Response?->Code ?? '') === '00';
    }

    public function getTransactionId(): ?string
    {
        return $this->response->Transaction?->RetrefNum ?? null;
    }

    public function getMessage(): string
    {
        return implode('', array_filter([
            $this->response->Transaction?->Response?->ErrorMsg ?? '',
            $this->response->Transaction?->Response?->SysErrMsg ?? '',
        ]));
    }

    public function getCode(): ?string
    {
        return $this->response->Transaction?->Response?->ReasonCode ?? null;
    }

    public function getData(): stdClass
    {
        return $this->response;
    }
}

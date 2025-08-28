<?php

namespace Omnipay\Garantibbva;

use Omnipay\Common\AbstractGateway;
use Omnipay\Garantibbva\Constants\Secure3DSecurityLevel;
use Omnipay\Garantibbva\Message\EnrolmentRequest;
use Omnipay\Garantibbva\Message\FetchTransactionRequest;
use Omnipay\Garantibbva\Traits\PurchaseGettersSetters;

/**
 * Garantibbva Gateway
 * (c) Tolga Can Günel
 * 2015, mobius.studio
 * http://www.github.com/tcgunel/omnipay-garantibbva
 * @method \Omnipay\Common\Message\NotificationInterface acceptNotification(array $options = [])
 * @method \Omnipay\Common\Message\RequestInterface completeAuthorize(array $options = [])
 */
class Gateway extends AbstractGateway
{
    use PurchaseGettersSetters;

    public function getName(): string
    {
        return 'Garantibbva';
    }

    public function getDefaultParameters()
    {
        return [
            'clientIp'                 => '127.0.0.1',
            'secure'                   => false,
            'api_version'              => 512,
            'lang'                     => 'tr',
            'terminal_user_id'         => 'GARANTI',
            'installment'              => 1,
            'secure_3d_security_level' => Secure3DSecurityLevel::PAY,
        ];
    }

    public function enrolment(array $parameters = [])
    {
        return $this->createRequest(EnrolmentRequest::class, $parameters);
    }

    public function fetchTransaction(array $parameters = [])
    {
        return $this->createRequest(FetchTransactionRequest::class, $parameters);
    }
}

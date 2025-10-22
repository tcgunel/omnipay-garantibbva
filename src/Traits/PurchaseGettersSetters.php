<?php

namespace Omnipay\Garantibbva\Traits;

use Omnipay\Garantibbva\Helpers\Helper;

trait PurchaseGettersSetters
{
    public function getTerminalMerchantId()
    {
        return $this->getParameter('terminal_merchant_id');
    }

    public function setTerminalMerchantId($value)
    {
        return $this->setParameter('terminal_merchant_id', $value);
    }

    public function getTerminalProvUserId()
    {
        return $this->getParameter('terminal_prov_user_id');
    }

    public function setTerminalProvUserId($value)
    {
        return $this->setParameter('terminal_prov_user_id', $value);
    }

    public function getProvUserPassword()
    {
        return $this->getParameter('prov_user_password');
    }

    public function setProvUserPassword($value)
    {
        return $this->setParameter('prov_user_password', $value);
    }

    public function getTerminalId()
    {
        return $this->getParameter('terminal_id');
    }

    public function setTerminalId($value)
    {
        return $this->setParameter('terminal_id', $value);
    }

    public function getStoreKey()
    {
        return $this->getParameter('store_key');
    }

    public function setStoreKey($value)
    {
        return $this->setParameter('store_key', $value);
    }

    public function getApiVersion()
    {
        return $this->getParameter('api_version');
    }

    public function setApiVersion($value)
    {
        return $this->setParameter('api_version', $value);
    }

    public function getInstallment()
    {
        return $this->getParameter('installment');
    }

    public function setInstallment($value)
    {
        return $this->setParameter('installment', $value);
    }

    public function getSecure()
    {
        return $this->getParameter('secure');
    }

    public function setSecure($value)
    {
        return $this->setParameter('secure', $value);
    }

    public function getTerminalNo()
    {
        return $this->getParameter('terminal_no');
    }

    public function setTerminalNo($value)
    {
        return $this->setParameter('terminal_no', $value);
    }

    public function getClientIp()
    {
        return Helper::getIPv4OrFallback($this->getParameter('client_ip'));
    }

    public function setClientIp($value)
    {
        $value = Helper::getIPv4OrFallback($value);

        return $this->setParameter('client_ip', $value);
    }

    public function getTerminalUserId()
    {
        return $this->getParameter('terminal_user_id');
    }

    public function setTerminalUserId($value)
    {
        return $this->setParameter('terminal_user_id', $value);
    }

    public function getTxnType()
    {
        return $this->getParameter('txn_type');
    }

    public function setTxnType($value)
    {
        return $this->setParameter('txn_type', $value);
    }

    public function getCompanyName()
    {
        return $this->getParameter('company_name');
    }

    public function setCompanyName($value)
    {
        return $this->setParameter('company_name', $value);
    }

    public function getLang()
    {
        return $this->getParameter('lang');
    }

    public function setLang($value)
    {
        return $this->setParameter('lang', $value);
    }

    public function getSecure3DSecurityLevel()
    {
        return $this->getParameter('secure_3d_security_level');
    }

    public function setSecure3DSecurityLevel($value)
    {
        return $this->setParameter('secure_3d_security_level', $value);
    }
}

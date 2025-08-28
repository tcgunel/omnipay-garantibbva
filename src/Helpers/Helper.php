<?php

namespace Omnipay\Garantibbva\Helpers;

use SimpleXMLElement;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class Helper
{
    public static function flattenArray(array $array): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, self::flattenArray($value, $key));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public static function is_hash_valid(array $data, string $store_key): bool
    {
        $seperator = ':';
        $hash_params_val = '';

        if (!empty($data['hashparams'])) {

            $params = array_filter(explode($seperator, $data['hashparams']));

            foreach ($params as $param) {
                $hash_params_val .= $data[$param];
            }

            $hash_params_val .= $store_key;
            $hashbytes = mb_convert_encoding($hash_params_val, 'ISO-8859-9', 'UTF-8');
            $hashCalculated = strtoupper(hash('sha512', $hashbytes));

            return strtoupper($hashCalculated) === $data['hash'];
        }

        return false;
    }

    public static function ArrayToXml($array, $rootElement = null, $xml = null)
    {
        $_xml = $xml;
        if ($_xml === null) {
            $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
        }

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                self::ArrayToXml($v, $k, $_xml->addChild($k));
            } else {
                $_xml->addChild($k, $v);
            }
        }

        return $_xml->asXML();
    }

    public static function XMLStringToObject($data)
    {
        $encoder = new XmlEncoder();
        $xml = $encoder->decode($data, 'xml');
        return (object)json_decode(json_encode($xml, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR);
    }
}

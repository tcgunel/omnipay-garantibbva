<?php

namespace Omnipay\Garantibbva\Helpers;

use SimpleXMLElement;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class Helper
{
    public static function getIPv4OrFallback(string $ip): string
    {
        // 1️⃣ If it's already IPv4, return it directly
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }

        // 2️⃣ If it's IPv6, check Cloudflare and proxy headers for IPv4
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $headers = [
                'HTTP_CF_CONNECTING_IP', // Cloudflare’s real client IP
                'HTTP_CF_REAL_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_CLIENT_IP',
                'HTTP_X_REAL_IP',
                'HTTP_X_FORWARDED',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED'
            ];

            foreach ($headers as $header) {
                if (!empty($_SERVER[$header])) {
                    // Cloudflare and proxies may send multiple comma-separated IPs
                    $ips = explode(',', $_SERVER[$header]);
                    foreach ($ips as $candidate) {
                        $candidate = trim($candidate);
                        if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                            return $candidate;
                        }
                    }
                }
            }
        }

        // 3️⃣ Fallback: use the server’s own IPv4 address
        $serverIp = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());
        if (filter_var($serverIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $serverIp;
        }

        // 4️⃣ Final fallback: localhost
        return '127.0.0.1';
    }

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

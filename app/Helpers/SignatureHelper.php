<?php

namespace App\Helpers;

class SignatureHelper
{
    public static function generate($apiKey, $secretKey,$timestamp)
    {
        $data= $timestamp.$apiKey;
        return hash_hmac('sha256', $data, $secretKey);
    }
}

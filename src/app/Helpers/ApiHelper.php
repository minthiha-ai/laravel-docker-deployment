<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use mysql_xdevapi\Exception;

class ApiHelper
{
    public static function admin_panel($url,$method,$data=null)
    {
        $adsConfig = config('apiConfig.admin');

        $apiKey = $adsConfig['api_key'];
        $secretKey = $adsConfig['secret_key'];
        $baseUrl = $adsConfig['base_url'];
        $timestamp = time();
        $endpoint = $baseUrl . $url;
        $signature = SignatureHelper::generate($apiKey,$secretKey,$timestamp);
        if($method == 'put'){
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' =>$apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->put($endpoint,$data);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());
            }
        }else if($method == 'post'){
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' => $apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->asJson()->post($endpoint, $data);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());

            }

        }else{
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' =>$apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->get($endpoint);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());
            }
        }
        $data=$response->json();
        if($response->status() != 200){
            Log::info($data);
            $data=[];
        }
        return $data;
    }
    public static function business_api($url,$method,$data=null)
    {
        $adsConfig = config('apiConfig.business_service');

        $apiKey = $adsConfig['api_key'];
        $secretKey = $adsConfig['secret_key'];
        $baseUrl = $adsConfig['base_url'];
        $timestamp = time();
        $endpoint = $baseUrl . $url;
        $signature = SignatureHelper::generate($apiKey,$secretKey,$timestamp);
        if($method == 'put'){
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' =>$apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->put($endpoint,$data);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());
            }
        }else if($method == 'post'){
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' => $apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->asJson()->post($endpoint, $data);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());

            }

        }else{
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' =>$apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->get($endpoint);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());
            }
        }
        $data=$response->json();
        if($response->status() != 200){
            Log::info($data);
            $data=[];
        }
        return $data;
    }

    public static function ads_api($url,$method,$data=null)
    {
        $adsConfig = config('apiConfig.ads_service');

        $apiKey = $adsConfig['api_key'];
        $secretKey = $adsConfig['secret_key'];
        $baseUrl = $adsConfig['base_url'];
        $timestamp = time();
        $endpoint = $baseUrl . $url;
        $signature = SignatureHelper::generate($apiKey,$secretKey,$timestamp);
        if($method == 'put'){
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' =>$apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->put($endpoint,$data);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());
            }
        }else if($method == 'post'){
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' => $apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->asJson()->post($endpoint, $data);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());

            }

        }else{
            try {
                $response = Http::withOptions(['verify' => false])->withHeaders([
                    'API-KEY' =>$apiKey,
                    'SIGNATURE' => $signature,
                    'TIMESTAMP' => $timestamp,
                ])->get($endpoint);
            }catch (Exception $exception){
                Log::info('AdsApi Helper error',$exception->getMessage());
            }
        }
        $data=$response->json();
        if($response->status() != 200){
            Log::info($data);
            $data=[];
        }
        return $data;
    }

}

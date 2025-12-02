<?php

namespace App\Classes;

class CurlRequest
{
    public function get($url, $referer='')
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        $referer = 'Referer: ' . $referer;
        curl_setopt($ch, CURLOPT_HTTPHEADER, [$referer]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

}

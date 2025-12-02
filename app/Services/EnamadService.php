<?php

namespace App\Services;

use App\Classes\CurlRequest;

class EnamadService
{
    public function getEmails($fromPage=1): array
    {
        /*
         * TODO LIST
         * done 1. curl and get enamad from https://www.enamad.ir/DomainListForMIMT/Index/6667
         * done 2. find domain and its link
         * done 3. curl finded link with domain refer
         * done 4. find email with structure xxx[at]xx.xx
         * 5. save into database
         */

        $curlRequest = new CurlRequest();

        $url = 'https://www.enamad.ir/DomainListForMIMT/Index/' . $fromPage;
        $firstPage = $curlRequest->get($url);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($firstPage);
        libxml_clear_errors();

        $output = [];
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//div[@id="Div_Content"]//a[contains(@href, "https://trustseal.enamad.ir/?id=")]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $domain = $link->nodeValue;
            parse_str(parse_url($href, PHP_URL_QUERY), $queryParams);
            if (isset($queryParams['id'])) {
                if (empty($queryParams['code']))
                    $href = $href.'xyz';
                $secondPage = $curlRequest->get($href, $domain);
                $secondPage = preg_replace('/\[at\]/', '@', $secondPage);

                $pattern = '/[a-zA-Z0-9._%+-]+@\w+\.\w{2,}(?!.*\benamad\b)/';

                preg_match_all($pattern, $secondPage, $matches);

                foreach ($matches[0] as $email) {
                    if (strpos($email, 'enamad') === false) {
                        echo $domain . ' ==> ' . $email . PHP_EOL . PHP_EOL;
                        $output [] = [
                            'domain' => $domain,
                            'email' => $email,
                        ];
                    }
                }
            }
        }

        return $output;
    }
}

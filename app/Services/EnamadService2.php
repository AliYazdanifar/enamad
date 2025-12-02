<?php

namespace App\Services;

use App\Classes\CurlRequest;

class EnamadService2
{
    public function getEmails($fromPage = 1): array
    {
        /*
         * TODO LIST
         * done 1. curl and get enamad from https://www.enamad.ir/DomainListForMIMT/Index/6667
         * done 2. find domain and its link
         * done 3. curl finded link with domain refer
         * done 4. find email with structure xxx[at]xx.xx
         * done 5. save into database
         *
         * requirement :
         * OK 1. saheb emtiyaz
         * OK 2. domain
         * OK 3. dastebandi
         * OK 4. email
         * OK 5. telephone
         * OK 6. ostan
         * OK 7. khedmat
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
            echo PHP_EOL."........." . $domain . PHP_EOL;
            parse_str(parse_url($href, PHP_URL_QUERY), $queryParams);
            if (isset($queryParams['id'])) {
                if (empty($queryParams['code']))
                    $href = $href . 'xyz';
                $secondPage = $curlRequest->get($href, $domain);
                $secondPage = preg_replace('/\[at\]/', '@', $secondPage);

                libxml_use_internal_errors(true);
                $dom->loadHTML($secondPage);
                libxml_clear_errors();
                $xpath = new \DOMXPath($dom);

                //extract email
                $emailNodes = $xpath->query("//div[contains(text(),'@')]");
                if ($emailNodes->length > 0) {
                    $email = $emailNodes->item(0)->textContent;
                } else {
                    echo "No email found.<br>";
                }

//extract owner
                $ownerNodes = $xpath->query("//div[contains(text(),'صاحب امتیاز :')]/following-sibling::div");
                if ($ownerNodes->length > 0) {
                    $owner = $ownerNodes->item(0)->textContent;
                } else {
                    echo "No owner found.<br>";
                }

                //ADDRESS
                $addressNodes = $xpath->query("//div[contains(text(),'آدرس:')]/following-sibling::div");
                if ($addressNodes->length > 0) {
                    $address = $addressNodes->item(0)->textContent;
                } else {
                    echo "No address found.<br>";
                }

                //telephone
                $phoneNodes = $xpath->query("//div[contains(text(),'تلفن:')]/following-sibling::div");
                if ($phoneNodes->length > 0) {
                    $phone = $phoneNodes->item(0)->textContent;
                } else {
                    echo "No phone found.<br>";
                }


                $servicesTableRows = $xpath->query("//h4[contains(text(), 'خدمات و مجوزهای کسب و کار')]/following::table[1]//tbody//tr");

                $services = [];
                foreach ($servicesTableRows as $row) {
                    $cols = $row->getElementsByTagName('td');
                    if ($cols->length > 0) {
                        $services[] = [
                            'service_title' => trim($cols->item(0)->textContent),
                            'issuer' => trim($cols->item(1)->textContent),
                            'license_number' => trim($cols->item(2)->textContent),
                            'start_date' => trim($cols->item(3)->textContent),
                            'end_date' => trim($cols->item(4)->textContent),
                            'status' => trim($cols->item(5)->textContent),
                        ];
                    }
                }
                $title = '';
                foreach ($services as $service) {
                    $title .= $service['service_title'] . ' - ';
                }

                $output[] = [
                    'owner' => trim($owner),
                    'domain' => trim($domain),
                    'category' => 'no cat',
                    'email' => trim($email),
                    'phone' => trim($phone),
                    'address' => trim($address),
                    'services' => trim($title),
                ];

            }
        }

        echo "........." . PHP_EOL . PHP_EOL . "page #$fromPage imported!" . PHP_EOL . PHP_EOL;

        return $output;
    }
}

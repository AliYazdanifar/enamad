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

    public function secondPage(string $href, $domain): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $href,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_REFERER => 'https://' . $domain,
            CURLOPT_USERAGENT => 'Mozilla/5.0',
        ]);

        $html = curl_exec($ch);

        if (!$html) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);


        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);

        $xpath = new \DOMXPath($dom);

        $rows = $xpath->query("//div[contains(@class,'mainul')]//div[contains(@class,'row')]");


        $result = [
            'owner' => null,
            'grantDate' => null,
            'validTo' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'responseTime' => null,
        ];


        foreach ($rows as $row) {

            $label = trim($xpath->query(".//div[contains(@class,'txtbold')]", $row)->item(0)->textContent);

            $value = trim(
                preg_replace(
                    '/\s+/',
                    ' ',
                    str_replace("\xC2\xA0", '', $xpath->query(".//div[contains(@class,'licontent') and not(contains(@class,'txtbold'))]", $row)->item(0)->textContent)
                )
            );


            switch (true) {

                case str_contains($label, 'صاحب امتیاز'):
                    $result['owner'] = $value;
                    break;

                case str_contains($label, 'تاریخ اعطا'):
                    $result['grantDate'] = str_replace('/', '-', $value);
                    break;

                case str_contains($label, 'تاریخ اعتبار'):

                    preg_match('/(\d{4})\/(\d{2})\/(\d{2})/', $value, $match);

                    if ($match) {

                        $result['validTo'] = "{$match[1]}-{$match[2]}-{$match[3]}";
                    }

                    break;

                case str_contains($label, 'آدرس'):
                    $result['address'] = $value;
                    break;

                case str_contains($label, 'تلفن'):
                    $result['phone'] = $value;
                    break;

                case str_contains($label, 'پست الكترونیكی'):
                    $result['email'] = str_replace('[at]', '@', $value);
                    break;

                case str_contains($label, 'ساعت پاسخگویی'):
                    $result['responseTime'] = $value;
                    break;
            }
        }

        return $result;
    }




}

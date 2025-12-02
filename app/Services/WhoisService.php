<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Iodev\Whois\Factory;

class WhoisService
{
    /**
     * @var \Iodev\Whois\Whois
     */
    private $whois;
    public function __construct()
    {
        $this->whois = Factory::get()->createWhois();

        $this->nictoken = '5479061531240302';//depositCode
        $this->authToken = 'R1VwRGZhVDI4ZVJidndpR3k4SFhXRURKdFd3c1hHR054ak0vSkhwUEM0SGIyZGRCazBtbi9PS25zQU1yVGc3dw==';
        $this->trid = 'BERTINA-7600';
        $this->certificate = dirname(__FILE__) . '/pe134-irnic.api.rsa.crt.pem'; // pem file name given from nic.ir like: 123456789_id123-irnic_D12345.pem

        $this->serverURL = 'https://epp.nic.ir/submit';
//        $this->serverURL = 'https://epp-test.nic.ir/submit';
        $this->adminContact = 'adminContact';
        $this->technicalContact = 'technicalContact';
        $this->billingContact = 'billingContact';

        $this->nameServers['ns1'] = 'ns46.phtco.com';
        $this->nameServers['ns2'] = 'ns47.phtco.com';

    }

    public function OtherTldWhois($domain)
    {
        try {
            sleep(2);
            Log::info('otherTldWhois : ' . $domain . ' => ' . $this->getRootDomain($domain));
            $tld = $this->getTld($domain);
            if ($tld == "ir")
                return [];
            $info = $this->whois->loadDomainInfo($this->getRootDomain($domain));
//            if (is_null($info) || !isset($info->expirationDate)) {
//                LOG::error('not found domain : ' . $domain);
//                return [
//                    'domain' => $domain,
//                    'tld' => $this->getTld($domain),
//                    'created' => 'is null',
//                    'exp_date' => 'is null',
//                    'owner' => 'is null',
//                ];
//
//            }

            $created = $info->creationDate == "" ? "" : date("Y-m-d", $info->creationDate);
            $exp = $info->expirationDate == "" ? "" : date("Y-m-d", $info->expirationDate);
            $owner = $info->owner == "" ? "" : $info->owner;

            return [
                'domain' => $domain,
                'tld' => $this->getTld($domain),
                'created' => $created,
                'exp_date' => $exp,
                'owner' => $owner,
            ];

        }catch (Exception $e){
            return [
                'domain' => $domain,
                'tld' => $this->getTld($domain),
                'created' => 'is null',
                'exp_date' => 'is null',
                'owner' => 'is null : '.$e->getMessage(),
            ];
        }
    }


    /**
     * @throws Exception
     */
    public function IRWhois($domain)
    {
        sleep(5);
        Log::info('IRWhois : ' . $domain . ' => ' . $this->getRootDomain($domain));

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>
                <epp xmlns="urn:ietf:params:xml:ns:epp-1.0">
                 <command>
                  <info>
                   <domain:info xmlns:domain="http://epp.nic.ir/ns/domain-1.0">
                	<domain:name>' . $this->getRootDomain($domain) . '</domain:name>
                	<domain:authInfo>
                	 <domain:pw>' . $this->nictoken . '</domain:pw>
                	</domain:authInfo>
                   </domain:info>
                  </info>
                  <clTRID>' . $this->trid . '</clTRID>
                 </command>
                </epp>';

        $response = $this->call($xml, false);
        $res = $this->xml2array($response);
        $resCode = $this->parseResCode($res);
        $resMsg = $this->parseResMessage($res);

        if ($resCode != 1000) {
            Log::error('domain = ' . $domain . ' msg = ' . $resMsg . ' code = ' . $resCode);
            return [
                'domain' => $domain,
                'tld' => "ir",
                'update' => $resMsg,
                'exp_date' => $resCode,
                'owner' => 'ERROR IN WHOIS',
            ];
        }
        $res = $this->parseDomainInfo($res);
        return [
            'domain' => $domain,
            'tld' => "ir",
            'update' => $res["upDate"],
            'exp_date' => $res["expDate"],
            'owner' => $res["contact"][0]["_v"],
        ];


    }

    public function call($xmlStr, $checkTime = true)
    {
        try {
            $xmlStr = stripslashes(trim($xmlStr));

            $response = $this->curlRequest($xmlStr);
            return $response;
        } catch (Exception $e) {
            $msg = 'exception in call method = ' . $e->getMessage();
            return $this->xmlError($msg);

        }
    }



    private function getTld($url)
    {
        if (strpos($url, '://') === false) {
            $url = 'http://' . $url;
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];

        $hostParts = explode('.', $host);

        return end($hostParts);
    }

    public function xml2array(&$string)

    {

        $parser = xml_parser_create();

        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);

        xml_parse_into_struct($parser, $string, $vals, $index);

        xml_parser_free($parser);

        $mnary = array();

        $ary =& $mnary;

        foreach ($vals as $r) {

            $t = $r['tag'];

            if ($r['type'] == 'open') {

                if (isset($ary[$t])) {

                    if (isset($ary[$t][0])) $ary[$t][] = array(); else $ary[$t] = array($ary[$t], array());

                    $cv =& $ary[$t][count($ary[$t]) - 1];

                } else $cv =& $ary[$t];

                if (isset($r['attributes'])) {

                    foreach ($r['attributes'] as $k => $v) $cv['_a'][$k] = $v;

                }

                $cv['_c'] = array();

                $cv['_c']['_p'] =& $ary;

                $ary =& $cv['_c'];

            } elseif ($r['type'] == 'complete') {

                if (isset($ary[$t])) { // same as open

                    if (isset($ary[$t][0])) $ary[$t][] = array(); else $ary[$t] = array($ary[$t], array());

                    $cv =& $ary[$t][count($ary[$t]) - 1];

                } else $cv =& $ary[$t];

                if (isset($r['attributes'])) {

                    foreach ($r['attributes'] as $k => $v) $cv['_a'][$k] = $v;

                }

                $cv['_v'] = (isset($r['value']) ? $r['value'] : '');

            } elseif ($r['type'] == 'close') {

                $ary =& $ary['_p'];

            }

        }


        $mnary = $this->del_P($mnary);


        return $mnary;

    }

    private function del_P(&$arr)
    {
        foreach ($arr as $k => $v) {

            if ($k === '_p')

                unset($arr[$k]);

            elseif (is_array($arr[$k]))

                $this->del_P($arr[$k]);

        }


        return $arr;

    }


    private function curlRequest($xml)
    {

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $headers = [
                'Authorization: Bearer ' . $this->authToken,

            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($ch, CURLOPT_SSLCERT, "$this->certificate");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "IRNIC_EPP_Client_Sample");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, "$this->serverURL");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

            $response = curl_exec($ch);

            if (curl_errno($ch) !== 0) {
                $response = '
					<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" >
						<response>
							<result code="-500" >
								<msg>Connection error: ' . curl_error($ch) . '</msg>
							</result>
						</response>
					</epp>
				';
            }

            curl_close($ch);
            return $response;

        } catch (Exception $e) {
            $response = '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0" >
						<response>
							<result code="-500" >
								<msg>' . $msg . '</msg>
							</result>
						</response>
					</epp>
				';

            return $response;
        }
    }

    public function parseResCode(array $irnicResultInArray)
    {
        return $irnicResultInArray['epp']['_c']['response']['_c']['result']['_a']['code'];
    }

    public function parseResMessage(array $irnicResultInArray)
    {
        return $irnicResultInArray['epp']['_c']['response']['_c']['result']['_c']['msg']['_v'];
    }


    public function parseDomainInfo(array $domainInfoResponse)
    {

        $info = $domainInfoResponse['epp']['_c']['response']['_c']['resData']['_c']['domain:infData']['_c'];

        $expDate = 'notSet';
        if (isset($info['domain:exDate']['_v']))
            $expDate = $info['domain:exDate']['_v'];


//        $nameServers = [];
//        foreach ($info['domain:ns']['_c']['domain:hostAttr'] as $key => $ns) {
//            $nameServers['ns' . ($key + 1)] = $ns['_c']['domain:hostName']['_v'];
//        }

        return [
            'message' => "pp",
            'name' => @$info['domain:name']['_v'],
            'expDate' => $expDate,
            'status' => @$info['domain:status'],
            'contact' => $info['domain:contact'],
            'upDate' => @$info['domain:upDate']['_v'],
        ];

    }

    public function getRootDomain($domain)
    {
        $url = preg_replace('/^https?:\/\//', '', $domain);

        $parts = explode('.', $url);

        if (count($parts) > 2) {
            $parts = array_slice($parts, -2);
        }

        return implode('.', $parts);
    }
}

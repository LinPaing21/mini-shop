<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MiniAppController extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Mini App']);
    }

    public function authToken(Request $request)
    {
        return $this->queryCusInfo($request->authToken);
    }

    public function queryCusInfo($authCode)
    {
        $merchCode = config('miniapp.merchant_code');
        $appid = config('miniapp.merchant_app_id');
        $appkey = config('miniapp.merchant_app_key');
        $queryCustInfoUrl = config('miniapp.baseUrl') . 'queryCustInfo';

        // return response()->json([$merchCode, $appid, $appkey, $queryCustInfoUrl]);
        $data = array(
            'Request' => array(
                'timestamp' => strval(time()),
                'method' => 'kbz.payment.queryCustInfo',
                'nonce_str' => str_replace('-', '', $this->uuidv4()),
                'version' => '1.0',
                'biz_content' => array(
                    'appid' => $appid,
                    'merch_code' => $merchCode,
                    'trade_type' => 'MINIAPP',
                    'access_token' => $authCode,
                    'resource_type' => 'OPENID'
                )
            )
        );

        $data['Request']['sign'] = $this->signature($data, $appkey);
        $data['Request']['sign_type'] = 'SHA256';

        // return response()->json($data);

        // Initialize Guzzle client
        $client = new Client();

        try {
            // Send POST request using Guzzle
            $response = $client->post($queryCustInfoUrl, [
                'json' => $obj,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            // Decode JSON response body
            $responseData = json_decode($response->getBody(), true);

            // Process the response data
            return response()->json($responseData);
        } catch (RequestException $e) {
            // Handle request exceptions (e.g., network errors)
            return response()->json(['error' => $e->getMessage()], 500);
        }
        // let data = {
        //     Request: {
        //         timestamp: Math.floor(date.now().getTime() / 1000) + '',
        //         method: 'kbz.payment.queryCustInfo',
        //         nonce_str: uuid.v4().replace(/-/g, ''),
        //         version: '1.0',
        //         biz_content: {
        //             appid: appid,
        //             merch_code: merchCode,
        //             trade_type: 'MINIAPP',
        //             access_token: authCode,
        //             resource_type: 'OPENID'
        //         }
        //     }
        // };
        // data.Request['sign'] = $this->signature(data, appkey);
        // data.Request['sign_type'] = 'SHA256';

        // console.debug("Request", queryCustInfoUrl, "with data", JSON.stringify(data, null, '\t'));

        // let response: CustInfoResponse = http.newClient().post(queryCustInfoUrl, {
        //     headers: {
        //         'Content-Type': 'application/json'
        //     },
        //     data: data
        // }).body.Response;

        // // console.debug("Response return", JSON.stringify(response, null, '\t'))

        // return response;
    }

    private function uuidv4() {
        $data = openssl_random_pseudo_bytes(16);
        assert(strlen($data) == 16);
    
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
    
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // Function to create a signature
    private function signature($obj, $key, $skips = array()) {
        // Initialize skips with default values
        $skips = array_merge($skips, ['sign', 'sign_type']);
        
        // Function to collect fields recursively
        $fields = array();
        $collect = function($name, $val) use (&$collect, &$fields, $skips) {
            if (in_array($name, $skips)) {
                return;
            }
            if (is_array($val) || is_object($val)) {
                foreach ($val as $subname => $subval) {
                    $collect($subname, $subval);
                }
            } else {
                $field = array_filter($fields, function($f) use ($name) {
                    return $f['key'] === $name;
                });
                $field = array_pop($field);
                if ($field) {
                    $field['val'] = $val;
                } else {
                    $fields[] = array('key' => $name, 'val' => $val);
                }
            }
        };
        
        // Initial collect call
        $collect('', $obj);
        
        // Sort fields by key
        usort($fields, function($a, $b) {
            return strcmp($a['key'], $b['key']);
        });
        
        // Construct string to hash
        $str = '';
        foreach ($fields as $field) {
            if ($field['val'] !== null && trim($field['val']) !== '') {
                $str .= $field['key'] . '=' . $field['val'] . '&';
            }
        }
        $str .= 'key=' . $key;
        
        // Output the string (for debugging)
        echo $str . "\n";
        
        // Create SHA-256 hash
        return strtoupper(hash('sha256', $str));
    }
}

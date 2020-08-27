<?php

/*
 *   Pure Storage® FlashArray™ API class
 *
 *  Run `composer dump-autoload` to enable the class
 *
 */

namespace App\Api;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FlashArrayApi
{
    // Constants
    public const FA_API_URI = "/api/1.10/";

    // Protected variables
    protected $url;
    protected $header;
    protected $apitoken;
    protected $cookieJar;
    protected $username;
    protected $authenticated;

    // Request method
    private function getRequest($request, $filter = [])
    {
        if (!$this->authenticated) {
            throw new RuntimeException('Unauthenticated API call. You need to authenticate to the API first.');
        }

        try {
            // Retrieve companies from API
            if (isset($filter)) {
                $response = Http::withOptions(
                    ['connect_timeout' => 20, 'verify' => false, 'cookies' => $this->cookieJar]
                )->withHeaders($this->header)->get($this->url . $request, $filter);
            } else {
                $response = Http::withOptions(
                    ['connect_timeout' => 20, 'verify' => false, 'cookies' => $this->cookieJar]
                )->withHeaders($this->header)->get($this->url . $request);
            }

            // If request is successful, return the body
            if ($response->successful()) {
                $result = json_decode($response->body(), true);
            } else {
                $result = null;
            }
        } catch (Exception $e) {
            Log::debug('xxx Error in FlashArrayApi connecting to "' . $this->url . '"');
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');

            return false;
        }
        return $result;
    }

    // Construct method
    public function __construct()
    {
        // Initialize ALSO MarketPlace API class
        $this->header           = array(
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent' => 'pso-explorer/' . config('app.version', 'unknown-version')
        );
        $this->apitoken         = null;
        $this->cookieJar        = null;
        $this->username         = null;
        $this->authenticated    = false;
    }

    // Authentication method
    public function authenticate($mgmtEndPoint, $apitoken)
    {
        // Set default result to false
        $result = false;
        $apiVersion = self::FA_API_URI;
        $this->apitoken = $apitoken;

        if (($mgmtEndPoint == '') or ($mgmtEndPoint == null)) {
            return $result;
        }

        try {
            $this->url = 'https://' . $mgmtEndPoint . '/api/api_version';
            $response = Http::withOptions(
                ['connect_timeout' => 20, 'verify' => false]
            )->withHeaders($this->header)->get($this->url);

            foreach (json_decode($response->body())->version as $item) {
                if (substr($item, 0, 2) == '1.') {
                    $apiVersion = $item;
                }
            }
            $this->url = 'https://' . $mgmtEndPoint . '/api/' . $apiVersion . '/';

            // Try to authenticate to API
            $response = Http::withOptions(
                ['connect_timeout' => 20, 'verify' => false]
            )->withHeaders($this->header)->post(
                $this->url . 'auth/session',
                [
                'api_token' => $this->apitoken,
                ]
            );

            // If request is successful, save the sessiontoken and add it to our header
            if ($response->successful()) {
                $this->cookieJar = $response->cookies();
                $this->username = json_decode($response->body())->username;
                $result = true;
            } else {
                Log::debug('xxx Error in FlashArrayApi connecting to "' . $this->url . '"');
                Log::debug('    - Message: "' . $e->getMessage() . '"');
                Log::debug('    - File: "' . $e->getFile() . '"');
                Log::debug('    - Line: "' . $e->getLine() . '"');

            }
        } catch (Exception $e) {
            // TO DO Should add more error handling...
            throw $e;
        }

        // Set class to authenticated
        $this->authenticated = $result;
        return $result;
    }

    public function getVolumes($filter = [])
    {
        return $this->getRequest('volume', $filter);
    }

    public function getArray($filter = [])
    {
        return $this->getRequest('array', $filter);
    }

    public function getPort($filter = [])
    {
        return $this->getRequest('port', $filter);
    }
}

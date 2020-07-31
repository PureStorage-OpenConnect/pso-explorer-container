<?php

/*
 *   Pure Storage® FlashBlade® API class
 *
 *  Run `composer dump-autoload` to enable the class
 *
 */

namespace App\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FlashBladeApi
{
    // Constants
//    public const FA_API_URI = "/api/1.17/";

    // Protected variables
    protected $url;
    protected $header;
    protected $username;
    protected $authenticated;
    protected $apiVersions;

    // Request method
    private function getRequest($request, $filter = [])
    {
        if (!$this->authenticated) {
            throw new ConnectionException('Unauthenticated API call. You need to authenticate to the API first.');
        }

        try {
            $response = Http::withOptions(
                ['connect_timeout' => 20, 'verify' => false]
            )->withHeaders($this->header)->get($this->url . $request, $filter);

            // If request is successful, return the body
            if ($response->successful()) {
                $result = json_decode($response->body(), true);
            } else {
                $result = null;
            }
        } catch (Exception $e) {
            Log::debug('xxx Error in FlashBladeApi connecting to "' . $this->url . '"');
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');

            throw $e;
        }
        return $result;
    }

    // Construct method
    public function __construct($mgmtEndPoint, $apitoken)
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
        $this->url = 'https://' . $mgmtEndPoint;
        $this->apitoken = $apitoken;
    }

    // Authentication method
    public function authenticate()
    {
        // Set default result to false
        $this->authenticated = false;
        $myheader = array_merge($this->header, array('api-token' => $this->apitoken));

        try {
            // Try to authenticate to API
            $response = Http::withOptions(
                ['connect_timeout' => 20, 'verify' => false]
            )->withHeaders($myheader)->post($this->url . '/api/login');
            $result = json_decode($response->body(), true);

            if (isset($response->headers()['x-auth-token']) and empty($result['error'])) {
                // If a x-auth-token is returned and there are no 'error's, we're logged in to FlashBlade®
                // and we're good to continue
                $this->username = json_decode($response->body())->username;
                $this->header = array_merge(
                    $this->header,
                    array('x-auth-token' => $response->headers()['x-auth-token'][0])
                );

                $response = Http::withOptions(
                    ['connect_timeout' => 20, 'verify' => false]
                )->withHeaders($myheader)->get($this->url . '/api/api_version');

                $result = json_decode($response->body(), true);
                $this->apiVersions = end($result['versions']);
                $this->url = $this->url . '/api/' . $this->apiVersions . '/';
                $this->authenticated = true;
            } else {
                Log::debug('xxx Unable to authenticate to FlashBlade® at "' . $this->url . '"');

                $this->authenticated = false;
            }
        } catch (ConnectionException $e) {
            Log::debug('xxx Error in FlashBladeApi connecting to "' . $this->url . '"');
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');

            $this->authenticated = false;
        }

        // Set class to authenticated
        return $this->authenticated;
    }

    public function getFileSystems($filter = [])
    {
        return $this->getRequest('file-systems', $filter);
    }

    public function getFileSystemsPerformance($filter = [])
    {
        return $this->getRequest('file-systems/performance', $filter);
    }

    public function getArray($filter = [])
    {
        return $this->getRequest('arrays', $filter);
    }
}

<?php

/*
 *   GitHub API class
 *
 */

namespace App\Api;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubApi
{
    // Constants
    public const GITHUB_REPO_API_URL = "https://api.github.com/repos";
    public const GITHUB_VALUES_URL = "https://raw.githubusercontent.com";
    public const TIMEOUT = 30;

    // Protected variables
    protected $gitRepoUri;
    protected $gitValuesUri;


    // Construct method
    public function __construct($gitRepoUri, $gitValuesUri)
    {
        $this->gitRepoUri = $gitRepoUri;
        $this->gitValuesUri = $gitValuesUri;
    }

    public function releases()
    {
        try {
            $url = $this::GITHUB_REPO_API_URL . $this->gitRepoUri . '/releases';

            $response = Http::withOptions(
                ['connect_timeout' => $this::TIMEOUT, 'verify' => true]
            )->get($url);
        } catch (ConnectionException $e) {
            Log::debug('xxx Error in GitHubApi connecting to "' . $url . '"');
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');

            throw $e;
        }

        $result = json_decode($response->body());
        if (isset($result->message) and ($result->message == 'Not Found')) {
            Log::debug('xxx GitHubApi returned an error while connecting to "' . $url . '"');
            Log::debug('    - Message: "Not Found"');

            throw new ConnectionException('Not Found');
        }

        return $result;
    }

    public function values($release)
    {
        try {
            if (substr(str_replace('v', '', $release), 0, 5) == '6.0.0') {
                $this->gitValuesUri = '/pureStorageDriver/values.yaml';
            }
            $url = $this::GITHUB_VALUES_URL . $this->gitRepoUri . '/' . $release . '/' . $this->gitValuesUri;

            $response = Http::withOptions(
                ['connect_timeout' => $this::TIMEOUT, 'verify' => true]
            )->get($url);
        } catch (ConnectionException $e) {
            Log::debug('xxx Error in GitHubApi connecting to "' . $url . '"');
            Log::debug('    - Message: "' . $e->getMessage() . '"');
            Log::debug('    - File: "' . $e->getFile() . '"');
            Log::debug('    - Line: "' . $e->getLine() . '"');

            throw $e;
        }

        return $response->body();
    }
}
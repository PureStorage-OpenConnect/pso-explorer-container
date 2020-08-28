<?php

/*
 *   GitHub API class
 *
 */

namespace App\Api;

use GuzzleHttp\Exception\ConnectException;
use Illuminate\Support\Facades\Http;
//use Illuminate\Support\Facades\Log;

class GitHubApi
{
    // Constants
    public const GITHUB_REPO_API_URL = "https://api.github.com/repos";
    public const GITHUB_VALUES_URL = "https://raw.githubusercontent.com";

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
                ['connect_timeout' => 20, 'verify' => true]
            )->get($url);
        } catch (ConnectException $e) {
            return false;
        }

        return json_decode($response->body(), true);
    }

    public function values($release)
    {
        try {
            $url = $this::GITHUB_VALUES_URL . $this->gitRepoUri . '/' . $release . '/' . $this->gitValuesUri;

            $response = Http::withOptions(
                ['connect_timeout' => 20, 'verify' => true]
            )->get($url);
        } catch (ConnectException $e) {
            return false;
        }

        return $response->body();
    }
}
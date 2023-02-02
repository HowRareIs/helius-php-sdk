<?php

namespace HowRareIs\HeliusPhpSdk\Handlers;

use HowRareIs\HeliusPhpSdk\Exceptions\HeliusException;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;

class RequestHandler {

    /** @var string API key */
    private string $api_key;

    /** @var array Headers */
    private array $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ];

    /**
     * @param string $api_key Helius API key
     */
    public function __construct(string $api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Helper method to make POST requests
     *
     * @param string $url     API endpoint
     * @param array $request Request body
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
     * @throws HeliusException
     */
    public function post(string $url, array $request) {

        $response = (new HttpFactory())->acceptJson()
            ->withHeaders($this->headers)
            ->post($url . '?api-key=' . $this->api_key, $request);

        return $this->handleResponse($response);
    }

    /**
     * Helper function to make a GET request
     *
     * @param string $url URL to request
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
     * @throws HeliusException
     */
    public function get(string $url) {

        $response = (new HttpFactory())->acceptJson()
            ->withHeaders($this->headers)
            ->get($url . '?api-key=' . $this->api_key);

        return $this->handleResponse($response);
    }

    /**
     * Helper function to make a DELETE request
     *
     * @param string $url URL to request
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
     * @throws HeliusException
     */
    public function delete(string $url) {

        $response = (new HttpFactory())->acceptJson()
            ->withHeaders($this->headers)
            ->delete($url . '?api-key=' . $this->api_key);

        return $this->handleResponse($response);
    }

    /**
     * Helper function to make a PUT request
     *
     * @param string $url     URL to request
     * @param array $request Request body
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response
     * @throws HeliusException
     */
    public function put(string $url, array $request) {

        $response = (new HttpFactory())->acceptJson()
            ->withHeaders($this->headers)
            ->put($url . '?api-key=' . $this->api_key, $request);

        return $this->handleResponse($response);
    }

    /**
     * Helper to remove some code duplication while there is no custom handling
     *
     * @param \GuzzleHttp\Promise\PromiseInterface|\Illuminate\Http\Client\Response $response Response from the server
     * @return mixed
     * @throws HeliusException
     */
    protected function handleResponse($response) {

        if ($response->failed()) {
            $error_body = json_decode($response->body(), true);
            $error_info = $error_body['error'] ?? 'Unknown error';
            throw new HeliusException('Bad response from server. ' . $error_info);
        }
        if ($response->status() !== 200) {
            $error_body = json_decode($response->body(), true);
            $error_info = $error_body['error'] ?? 'Unknown error';
            throw new HeliusException('Bad status code: ' . $response->status() . ' ' . $error_info);
        }

        return $response;
    }
}
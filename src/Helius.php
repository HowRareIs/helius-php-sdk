<?php

namespace HowRareIs\HeliusPhpSdk;

use HowRareIs\HeliusPhpSdk\Exceptions\HeliusException;
use HowRareIs\HeliusPhpSdk\Handlers\RequestHandler;

class Helius {

    const API_URL_V0 = "https://api.helius.xyz/v0/";
    const API_URL_V1 = "https://api.helius.xyz/v1/";

    CONST ENDPOINTS = [
        'fingerprint' => 'nfts',
        'mints' => 'mintlist',
        'webhooks' => 'webhooks',
    ];
    /**
     * @var RequestHandler
     */
    private RequestHandler $request_handler;

    public function __construct(string $api_key) {

        $this->request_handler = new RequestHandler($api_key);
    }

    /**
     * Get all webhooks
     *
     * @return array
     * @throws HeliusException
     */
    public function getAllWebhooks(): array
    {

        $response = $this->request_handler->get(self::API_URL_V0 . self::ENDPOINTS['webhooks']);
        $data = json_decode($response->body(), true);

        if ($data === false) {
            throw new HeliusException('Failed to decode response body.');
        }
        return $data;
    }

    /**
     * Get webhook by ID
     *
     * @param string $webhook_id Webhook ID
     *
     * @return array
     * @throws HeliusException
     */
    public function getWebhookById(string $webhook_id): array
    {

        $response = $this->request_handler->get(self::API_URL_V0 . self::ENDPOINTS['webhooks'] . '/' . $webhook_id);
        $data = json_decode($response->body(), true);

        if ($data === false) {
            throw new HeliusException('Failed to decode response body.');
        }
        return $data;
    }

    /**
     * Create webhook
     *
     * @param array $webhook_data Webhook data
     *
     * @return array
     * @throws HeliusException
     */
    public function createWebhook(array $webhook_data): array
    {
        if (empty($webhook_data['accountAddresses'])) {
            throw new \InvalidArgumentException('Webhook must have at least one account address.');
        }
        if (count($webhook_data['accountAddresses']) > 100_000) {
            throw new \LengthException('Webhook can have maximum 100_000 addresses.');
        }
        $response = $this->request_handler->post(self::API_URL_V0 . self::ENDPOINTS['webhooks'], $webhook_data);
        $data = json_decode($response->body(), true);

        if ($data === false) {
            throw new HeliusException('Failed to decode response body.');
        }

        return $data;
    }

    /**
     * Abstracted method to create webhook for a collection. It will overwrite any passed accountAddresses with mintlist.
     *
     * @param array $collection_data Collection data
     * @param array $webhook_data    Webhook data
     *
     * @return array
     * @throws HeliusException
     */
    public function createCollectionWebhook(array $collection_data, array $webhook_data): array
    {
        $mintlist = $this->getMintlist($collection_data, true);
        if (empty($mintlist)) {
            throw new HeliusException('Cant create webhook. Failed to retrieve mintlist for collection.');
        }

        return $this->createWebhook(array_merge($webhook_data, ['accountAddresses' => $mintlist]));
    }
    /**
     * Delete webhook
     *
     * @param string $webhook_id Webhook ID
     *
     * @return void
     * @throws HeliusException
     */
    public function deleteWebhook(string $webhook_id): void
    {
        $this->request_handler->delete(self::API_URL_V0 . self::ENDPOINTS['webhooks'] . '/' . $webhook_id);
    }
    /**
     * Edit webhook
     *
     * @param string $webhook_id      Webhook ID
     * @param array $new_webhook_data New webhook data to replace old data
     *
     * @return array
     * @throws HeliusException
     */
    public function editWebhook(string $webhook_id, array $new_webhook_data): array
    {

        $webhook = $this->getWebhookById($webhook_id);
        unset($webhook['webhookID'], $webhook['wallet']);

        $webhook = array_merge($webhook, $new_webhook_data);
        if (count($webhook['accountAddresses']) > 100_000) {
            throw new \LengthException('Webhook can have maximum 100_000 addresses.');
        }

        $response = $this->request_handler->put(self::API_URL_V0 . self::ENDPOINTS['webhooks'] . '/' . $webhook_id, $webhook);
        $data = json_decode($response->body(), true);

        if ($data === false) {
            throw new HeliusException('Failed to decode response body.');
        }

        return $data;
    }

    /**
     * Add additional addresses to webhook
     *
     * @param string $webhook_id           Webhook ID
     * @param array  $additional_addresses Additional addresses
     *
     * @return array
     * @throws HeliusException
     */
    public function appendAddressesToWebhook(string $webhook_id, array $additional_addresses): array
    {

        $webhook = $this->getWebhookById($webhook_id);
        unset($webhook['webhookID'], $webhook['wallet']);
        $webhook['accountAddresses'] = array_merge($webhook['accountAddresses'], $additional_addresses);

        if (count($webhook['accountAddresses']) > 100_000) {
            throw new \LengthException('Webhook can have maximum 100_000 addresses.');
        }

        $response = $this->request_handler->put(self::API_URL_V0 . self::ENDPOINTS['webhooks'] . '/' . $webhook_id, $webhook);

        $data = json_decode($response->body(), true);

        if ($data === false) {
            throw new HeliusException('Failed to decode response body.');
        }

        return $data;
    }

    /**
     * Get mintlist from query
     *
     * @param array $query       Query parameters
     * @param bool  $only_hashes Return only hashes
     *
     * @return array
     * @throws HeliusException
     */
    public function getMintlist(array $query, bool $only_hashes = false): array
    {

        if (empty($query)) {
            throw new \InvalidArgumentException ("Query cannot be empty");
        }

        if (empty($query['firstVerifiedCreators']) && empty($query['verifiedCollectionAddresses'])) {
            throw new \InvalidArgumentException ("Query must contain either 'firstVerifiedCreators' or 'verifiedCollectionAddresses' parameter");
        }

        // prefer verifiedCollectionAddress over firstVerifiedCreator
        if (!empty($query['verifiedCollectionAddresses'])) {
            $query_key = 'verifiedCollectionAddresses';
            $query_value = $query['verifiedCollectionAddresses'];
        } else {
            $query_key = 'firstVerifiedCreators';
            $query_value = $query['firstVerifiedCreators'];
        }

        $request = [
            'query' => [
                $query_key => $query_value,
            ],
            'options' => [
                'limit' => 10000
            ],
        ];
        $return = [];
        $break_counter = 0;
        while(true) {

            $response = $this->request_handler->post(self::API_URL_V1 . self::ENDPOINTS['mints'], $request);
            $data = json_decode($response->body(), true);

            if ($data === false) {
                throw new HeliusException('Failed to decode response body.');
            }

            if (!empty($data['result'])) {
                $return = array_merge($return, $data['result']);
            }

            if (!empty($data['paginationToken'])) {
                $request['options']['paginationToken'] = $data['paginationToken'];
            } else {
                break;
            }

            if ($break_counter > 10) {
                throw new HeliusException('Break counter reached. Panic!');
            }
            $break_counter++;
        }
        if ($only_hashes) {
            $return = array_map(function($item) {
                return $item['mint'];
            }, $return);
        }
        return $return;

    }

    /**
     * Get mintlist by collection address
     *
     * @param string $collection_address Collection address
     * @param bool   $only_hashes        Return only hashes
     *
     * @return array
     * @throws HeliusException
     */
    public function getMintlistByCollectionAddress(string $collection_address, bool $only_hashes = false): array
    {
        return $this->getMintlist(['verifiedCollectionAddresses' => [$collection_address]], $only_hashes);
    }

    /**
     * Get mintlist by creator address
     *
     * @param string $creator_address Creator address
     * @param bool   $only_hashes     Return only hashes
     *
     * @return array
     * @throws HeliusException
     */
    public function getMintlistByCreatorAddress(string $creator_address, bool $only_hashes = false): array
    {
        return $this->getMintlist(['firstVerifiedCreators' => [$creator_address]], $only_hashes);
    }

    /**
     * Get NFT fingerprints
     *
     * @param string|array $mints         Mint hash or array of mint hashes
     * @param string       $extract_field Field to extract from response array.
     *
     * @return array
     * @throws HeliusException
     */
    public function getNftFingerprints(string|array $mints, string $extract_field = ''): array
    {

        if (is_string($mints)) {
            $mints = [$mints];
        }

        if (count($mints) > 1_000) {
            throw new \LengthException('Too many mints. Max 1_000');
        }

        $response = $this->request_handler->post(self::API_URL_V1 . self::ENDPOINTS['fingerprint'], [
            'mints' => $mints
        ]);

        $data = json_decode($response->body(), true);
        if ($data === false) {
            throw new HeliusException('Failed to decode response body.');
        }

        if ($extract_field == '') {
            return $data;
        }

        $return = [];
        foreach ($data as $item) {
            $return[$item['mint']] = $item[$extract_field] ?? false;
        }
        return $return;

    }

    /**
     * Get mintlist from NFT hash (mint)
     *
     * @param string $mint        NFT hash
     * @param bool   $only_hashes Return only hashes
     *
     * @return array
     * @throws HeliusException
     */
    public function getMintlistFromNft(string $mint, bool $only_hashes = false): array
    {

        $fingerprints = $this->getNftFingerprints($mint);

        if (!empty($fingerprints[0]['verifiedCollectionAddress'])) {
            return $this->getMintlistByCollectionAddress($fingerprints[0]['verifiedCollectionAddress'], $only_hashes);
        } else if (!empty($fingerprints[0]['firstVerifiedCreator'])) {
            return $this->getMintlistByCreatorAddress($fingerprints[0]['firstVerifiedCreator'], $only_hashes);
        }
        throw new HeliusException('Failed to get mintlist from NFT. Valid fingerprint not retrieved.');
    }

}
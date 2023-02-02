# Helius PHP SDK

Unofficial Helius PHP SDK is an opinionated (i.e. might not follow the same conventions as TypeScript version) port of the official [Helius SDK](https://github.com/helius-labs/helius-sdk/)

Not all features from the official SDK are implemented. Some will never be implemented, because they are not needed in PHP version of the SDK. Some missing features will be implemented in the future. This SDK will have features or conveniences that are not present in the official SDK.

Helius API is very functional and not all of its features are covered by this SDK. If you want some new features to be added / new endpoints to be handled, open an issue or create a pull request.

Consult official Helius [API docs](https://docs.helius.xyz) before using SDK to learn about methods and their limitations. In most cases SDK will not handle data validation. That's responsibility of the end user.

If you don't have Helius API key yet, you can get one [here](https://dev.helius.xyz/dashboard/app).

## Installation

You can install the package via composer:

```
composer require howrareis/helius-php-sdk
```

## Usage

```
use HowRareIs\HeliusPhpSdk\Helius;

$helius = new Helius('<your-api-key>'); // replace <your-api-key> with your API key
```

## Webhooks

Consult official Helius [API docs.](https://docs.helius.xyz) before using SDK to learn about webhooks and their limitations.

### Create webhook
```php
$webhook_data = [
    'webhookURL' => 'https://example.com/webhook',
    'accountAddresses' => [
        Addresses::TENSOR,
        '4B4a93ekt1fmXSVkTrULaxVFHkmME82TUi5Cyc5aF7K',
    ],
    'transactionTypes' => [
        TransactionTypes::NFT_LISTING,
        TransactionTypes::NFT_CANCEL_LISTING,
    ],
    'webhookType' => WebhookType::ENHANCED,
    'authHeader' => 'someouthkey',
];
$webhook = $helius->createWebhook($webhook_data);
```

### Get all webhooks

```php
$webhooks = $helius->getAllWebhooks();
```

### Get webhook by id

```php
$webhook = $helius->getWebhookById('11111111-1111-1111-1111-111111111111'); // replace 11111111-1111-1111-1111-111111111111 with your webhook id
```

### Edit webhook
In the API call to update webhook you need to submit full data for the webhook. To make updates more convenient ``editWebhook()`` method will retrieve original data 
and overwrite only parts you have specified in the update.
```php
$update = [
    'transactionTypes' => [
        TransactionTypes::NFT_MINT,
        TransactionTypes::NFT_BID,
    ],
];

$webhook = $helius->editWebhook($webhook_id, $update);
```

### Append addresses to webhook
Max 100 000 addresses can be added to the webhook. If you try to add more than that, you will get an error.
```php
$webhook = $helius->appendAddressesToWebhook($webhook_id, [Addresses::HYPERSPACE]);
```

### Delete webhook

```php
$helius->deleteWebhook($webhook_id);
```

### Create collection webhook
This is another convenience method. Behind the scenes it will try to fetch mintlist for the collection and will create a webhook with all NFT addresses from the mintlist.
```php 
$payload = [
    'webhookURL' => 'https://example.com/webhook',
    'transactionTypes' => [
        TransactionTypes::NFT_SALE
    ],
    'webhookType' => WebhookType::ENHANCED,
    'authHeader' => 'someouthkey',
];
$webhook = $helius->createCollectionWebhook(Collections::ABC, $webhook_rules);
```

### Get mintlist
All mintlist retrieval methods by default will return unmodified data from API response. It will include mints and names of the NFTs. However, if you are interested only in NFT addresses you can pass second argument as true. That will extract only NFT addresses from the response and will return as one array.

```php
$mintlist = $helius->getMintlist(Collections::ABC);
```
or
```php
$request = [
    'verifiedCollectionAddresses' => ['SMBH3wF6baUj6JWtzYvqcKuj2XCKWDqQxzspY12xPND'],
];
$mintlist = $helius->getMintlist($request, true);
```

### Get mintlist from Collection address

```php
$mintlist = $helius->getMintlistByCollectionAddress($colllection_addrress);
```

### Get mintlist from Creator address

```php
$mintlist = $helius->getMintlistByCreatorAddress($creator_address, true);
```


### Get mintlist from NFT address (mint hash)
Behind the scenes SDK will first call NFT Fingerprint API to find out Creator address and Collection address. If any of the two will be found
they will be used to retrieve mintlist. Collection address will have higher priority.
```php
$mintlist = $helius->getMintlistFromNft($nft_mint, true);
```

### Get fingerprints for NFTs

You can pass one mint as a string or multiple mints as an array. Max 1000 mints per request.
```php
$fingerprints = $helius->getNftFingerprints($mint_hashes);
```
If you want to extract one specific field from returned data (like activeListings) you can use second argument.
```php
$active_listings = $helius->getNftFingerprints($mint_hashes, 'activeListings');
```
In response, you will get associated array with mint hashes as keys and values as requested field. If field is not found, you will get false.


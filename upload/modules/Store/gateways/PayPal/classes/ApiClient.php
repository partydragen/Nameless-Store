<?php
/**
 * ApiClient
 * Handles PayPal API requests and authentication
 *
 * @package Modules\Store
 */
namespace Store\Gateways\PayPal;

use StoreConfig;

trait ApiClient {

    private $api_url = 'https://api-m.sandbox.paypal.com'; // Use 'https://api-m.sandbox.paypal.com' for sandbox

    public function getAccessToken(): ?string {
        $client_id = StoreConfig::get('paypal.client_id');
        $client_secret = StoreConfig::get('paypal.client_secret');

        if (!$client_id || !$client_secret) {
            $this->logError('Client ID and Client Secret not set up');
            $this->addError('Administration have not completed the configuration of this gateway!');
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->api_url}/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, "{$client_id}:{$client_secret}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Accept-Language: en_US']);

        $response = json_decode(curl_exec($ch), true);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200 && isset($response['access_token'])) {
            $hook_key = StoreConfig::get('paypal.hook_key');
            if (!$hook_key) {
                return $this->createWebhook($response['access_token']) ? $response['access_token'] : null;
            }
            return $response['access_token'];
        }

        $this->logError('Failed to obtain access token: ' . json_encode($response));
        $this->addError('PayPal integration incorrectly configured!');
        return null;
    }

    public function makeApiRequest(string $endpoint, string $method, string $access_token, $data = []): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->api_url}{$endpoint}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json'
        ]);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if (!empty($data)) {
                // Handle raw JSON string for verification endpoint
                if ($endpoint === '/v1/notifications/verify-webhook-signature' && is_string($data)) {
                    $json_data = $data;
                } else {
                    $json_data = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            }
        }

        $response = json_decode(curl_exec($ch), true) ?: [];
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code >= 400) {
            $this->logError("API request failed ($endpoint): " . json_encode($response));
        }

        $response["http_code"] = $http_code;
        return $response;
    }
}
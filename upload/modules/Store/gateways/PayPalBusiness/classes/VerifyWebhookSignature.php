<?php
/**
 * VerifyPayPalWebhookSignature class
 * Handles PayPal webhook signature verification
 *
 * @package Modules\Store
 */
namespace Store\Gateways\PayPalBusiness;

use StoreConfig;

class VerifyWebhookSignature {

    public string $auth_algo;
    public string $cert_url;
    public string $transmission_id;
    public string $transmission_sig;
    public string $transmission_time;
    public string $webhook_id;
    public string $request_body;

    public function __construct() {
        $headers = getallheaders();
        $headers = array_change_key_case($headers, CASE_UPPER);

        $this->setAuthAlgo($headers['PAYPAL-AUTH-ALGO']);
        $this->setCertUrl($headers['PAYPAL-CERT-URL']);
        $this->setTransmissionId($headers['PAYPAL-TRANSMISSION-ID']);
        $this->setTransmissionSig($headers['PAYPAL-TRANSMISSION-SIG']);
        $this->setTransmissionTime($headers['PAYPAL-TRANSMISSION-TIME']);
        $this->setWebhookId(StoreConfig::get('paypal_business.hook_key'));
    }

    /**
     * The algorithm that PayPal uses to generate the signature and that you can use to verify the signature. Extract this value from the `PAYPAL-AUTH-ALGO` response header, which is received with the webhook notification.
     *
     * @param string $auth_algo
     */
    public function setAuthAlgo(string $auth_algo): void {
        $this->auth_algo = $auth_algo;
    }

    /**
     * The algorithm that PayPal uses to generate the signature and that you can use to verify the signature. Extract this value from the `PAYPAL-AUTH-ALGO` response header, which is received with the webhook notification.
     *
     * @return string
     */
    public function getAuthAlgo(): string {
        return $this->auth_algo;
    }

    /**
     * The X.509 public key certificate. Download the certificate from this URL and use it to verify the signature. Extract this value from the `PAYPAL-CERT-URL` response header, which is received with the webhook notification.
     *
     * @param string $cert_url
     */
    public function setCertUrl(string $cert_url): void {
        $this->cert_url = $cert_url;
    }

    /**
     * The X.509 public key certificate. Download the certificate from this URL and use it to verify the signature. Extract this value from the `PAYPAL-CERT-URL` response header, which is received with the webhook notification.
     *
     * @return string
     */
    public function getCertUrl(): string {
        return $this->cert_url;
    }

    /**
     * The ID of the HTTP transmission. Contained in the `PAYPAL-TRANSMISSION-ID` header of the notification message.
     *
     * @param string $transmission_id
     */
    public function setTransmissionId(string $transmission_id): void {
        $this->transmission_id = $transmission_id;
    }

    /**
     * The ID of the HTTP transmission. Contained in the `PAYPAL-TRANSMISSION-ID` header of the notification message.
     *
     * @return string
     */
    public function getTransmissionId(): string {
        return $this->transmission_id;
    }

    /**
     * The PayPal-generated asymmetric signature. Extract this value from the `PAYPAL-TRANSMISSION-SIG` response header, which is received with the webhook notification.
     *
     * @param string $transmission_sig
     */
    public function setTransmissionSig(string $transmission_sig): void {
        $this->transmission_sig = $transmission_sig;
    }

    /**
     * The PayPal-generated asymmetric signature. Extract this value from the `PAYPAL-TRANSMISSION-SIG` response header, which is received with the webhook notification.
     *
     * @return string
     */
    public function getTransmissionSig(): string {
        return $this->transmission_sig;
    }

    /**
     * The date and time of the HTTP transmission. Contained in the `PAYPAL-TRANSMISSION-TIME` header of the notification message.
     *
     * @param string $transmission_time
     */
    public function setTransmissionTime(string $transmission_time): void {
        $this->transmission_time = $transmission_time;
    }

    /**
     * The date and time of the HTTP transmission. Contained in the `PAYPAL-TRANSMISSION-TIME` header of the notification message.
     *
     * @return string
     */
    public function getTransmissionTime(): string {
        return $this->transmission_time;
    }

    /**
     * The ID of the webhook as configured in your Developer Portal account.
     *
     * @param string $webhook_id
     */
    public function setWebhookId(string $webhook_id): void {
        $this->webhook_id = $webhook_id;
    }

    /**
     * The ID of the webhook as configured in your Developer Portal account.
     *
     * @return string
     */
    public function getWebhookId(): string {
        return $this->webhook_id;
    }

    /**
     * The content of the HTTP `POST` request body of the webhook notification you received as a string.
     *
     * @param string $request_body
     */
    public function setRequestBody(string $request_body): void {
        $this->request_body = $request_body;
    }

    /**
     * The content of the HTTP `POST` request body of the webhook notification you received as a string.
     *
     * @return string
     */
    public function getRequestBody(): string {
        return $this->request_body;
    }

    public function toJSON(): string {
        $payload = '{';
        $fields = [
            'auth_algo' => $this->auth_algo,
            'cert_url' => $this->cert_url,
            'transmission_id' => $this->transmission_id,
            'transmission_sig' => $this->transmission_sig,
            'transmission_time' => $this->transmission_time,
            'webhook_id' => $this->webhook_id
        ];
        foreach ($fields as $field => $value) {
            $escaped_value = addslashes($value);
            $payload .= "\"$field\":\"$escaped_value\",";
        }
        $payload .= '"webhook_event":' . $this->request_body;
        $payload .= '}';

        return $payload;
    }
}

<?php
/**
 * PlanManagerTrait
 * Manages PayPal plans and products
 *
 * @package Modules\Store
 */
namespace Store\Gateways\PayPalBusiness;

use DB;
use Product;
use Store;

trait PlanManager {
    public function getPlan(Product $product): ?string {
        $plan_query = DB::getInstance()->query('SELECT * FROM nl2_store_products_meta WHERE product_id = ? AND name = ?', [$product->data()->id, 'paypal_plan_id']);
        if ($plan_query->count()) {
            return $plan_query->first()->value;
        }

        $plan_id = $this->createPlan($product);
        if ($plan_id) {
            DB::getInstance()->insert('store_products_meta', [
                'product_id' => $product->data()->id,
                'name' => 'paypal_plan_id',
                'value' => $plan_id
            ]);
            return $plan_id;
        }

        return null;
    }

    public function createPlan(Product $product): ?string {
        $access_token = $this->getAccessToken();
        if (count($this->getErrors())) {
            return null;
        }

        // Get or create PayPal product
        $paypal_product_id = $this->getPayPalProductId($product);
        if (!$paypal_product_id) {
            $this->logError('Failed to retrieve or create PayPal product');
            return null;
        }

        $duration_json = json_decode($product->data()->durability, true) ?? [];

        $plan_data = [
            'product_id' => $paypal_product_id,
            'name' => $product->data()->name,
            'description' => $product->data()->name,
            'status' => 'ACTIVE',
            'billing_cycles' => [
                [
                    'frequency' => [
                        'interval_unit' => strtoupper($duration_json['period'] ?? 'MONTH'),
                        'interval_count' => $duration_json['interval'] ?? 1
                    ],
                    'tenure_type' => 'REGULAR',
                    'sequence' => 1,
                    'total_cycles' => 0,
                    'pricing_scheme' => [
                        'fixed_price' => [
                            'value' => Store::fromCents($product->data()->price_cents),
                            'currency_code' => Store::getCurrency()
                        ]
                    ]
                ]
            ],
            'payment_preferences' => [
                'auto_bill_outstanding' => true,
                'payment_failure_threshold' => 0
            ]
        ];

        $response = $this->makeApiRequest('/v1/billing/plans', 'POST', $access_token, $plan_data);
        if (isset($response['id'])) {
            return $response['id'];
        }

        $this->logError('Failed to create plan: ' . json_encode($response));
        return null;
    }

    private function getPayPalProductId(Product $product): ?string {
        // Check if a PayPal product_id exists in the database
        $product_query = DB::getInstance()->query('SELECT * FROM nl2_store_products_meta WHERE product_id = ? AND name = ?', [$product->data()->id, 'paypal_product_id']);
        if ($product_query->count()) {
            return $product_query->first()->value;
        }

        // Create a new product in PayPal
        $access_token = $this->getAccessToken();
        if (count($this->getErrors())) {
            return null;
        }

        $product_data = [
            'name' => $product->data()->name,
            'description' => $product->data()->name,
            'type' => 'SERVICE',
            'category' => 'SOFTWARE'
        ];

        $response = $this->makeApiRequest('/v1/catalogs/products', 'POST', $access_token, $product_data);
        if (isset($response['id'])) {
            $paypal_product_id = $response['id'];
            // Store the product_id in the database
            DB::getInstance()->insert('store_products_meta', [
                'product_id' => $product->data()->id,
                'name' => 'paypal_product_id',
                'value' => $paypal_product_id
            ]);
            return $paypal_product_id;
        }

        $this->logError('Failed to create PayPal product: ' . json_encode($response));
        return null;
    }
}
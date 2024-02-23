<?php
/**
 * PayPal_Gateway class
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.0.3
 * @license MIT
 */
class PayPal_Gateway extends GatewayBase {

    public function __construct() {
        $name = 'PayPal';
        $author = '<a href="https://partydragen.com" target="_blank" rel="nofollow noopener">Partydragen</a> and my <a href="https://partydragen.com/supporters/" target="_blank">Sponsors</a>';
        $gateway_version = '1.7.1';
        $store_version = '1.7.1';
        $settings = ROOT_PATH . '/modules/Store/gateways/PayPal/gateway_settings/settings.php';

        parent::__construct($name, $author, $gateway_version, $store_version, $settings);
    }

    public function onCheckoutPageLoad(TemplateBase $template, Customer $customer): void {
        // Not necessary
    }

    public function processOrder(Order $order): void {
        $paypal_email = StoreConfig::get('paypal/email');
        if ($paypal_email == null || empty($paypal_email)) {
            $this->addError('Administration have not completed the configuration of this gateway!');
            return;
        }

        $return_url = rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPal&do=success');
        $cancel_url = rtrim(URL::getSelfURL(), '/') . URL::build('/store/process/', 'gateway=PayPal&do=cancel');
        $listener_url = rtrim(URL::getSelfURL(), '/') . URL::build('/store/listener/', 'gateway=PayPal');

        echo '
            <form name="pay" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
              <input type="hidden" name="cmd" value="_xclick">
              <input type="hidden" name="business" value="' . $paypal_email . '" />
              <input type="hidden" name="currency_code" value="' . $order->getAmount()->getCurrency() . '" />
              <input type="hidden" name="amount" value="' . Store::fromCents($order->getAmount()->getTotalCents()) . '" />
              <input type="hidden" name="item_name" value="' . $order->getDescription() . '">
              <input type="hidden" name="item_number" value="' . $order->data()->id . '">
              <input type="hidden" name="no_shipping" value="1">
              <input type="hidden" name="custom" value="' . $order->data()->id . '">
              <input type="hidden" name="return" value="' . $return_url . '">
              <input type="hidden" name="cancel_return" value="' . $cancel_url . '">
              <input type="hidden" name="rm" value="2">
              <input type="hidden" name="notify_url" value="' . $listener_url . '" />
            </form>

            <script type="text/javascript">
                document.pay.submit();
            </script>
        ';
    }

    public function handleReturn(): bool {
        if (isset($_GET['do']) && $_GET['do'] == 'success') {
            $payment = new Payment($_POST['txn_id'], 'transaction');
            if (!$payment->exists()) {
                // Register pending payment
                $payment->create([
                    'order_id' => $_POST['custom'],
                    'gateway_id' => $this->getId(),
                    'transaction' => $_POST['txn_id'],
                    'created' => date('U'),
                    'last_updated' => date('U'),
                    'status_id' => 0,
                    'amount_cents' => Store::toCents($_POST['mc_gross']),
                    'currency' => $_POST['mc_currency'],
                    'fee_cents' => Store::toCents($_POST['mc_fee'] ?? 0)
                ]);
            }

            return true;
        }

        return false;
    }

    public function handleListener(): void {
        $raw_post_data = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost = [];
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2)
                $myPost[$keyval[0]] = urldecode($keyval[1]);
        }
        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        foreach ($myPost as $key => $value) {
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
        }

        $_POST = $myPost;

        //Post IPN data back to paypal to validate
        $ch = curl_init('https://ipnpb.paypal.com/cgi-bin/webscr');

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);

        if (!($res = curl_exec($ch))) {
            // error_log("Got " . curl_error($ch) . " when processing IPN data");
            curl_close($ch);

            ErrorHandler::logWarning('[Store] [PayPal Gateway] Curl error ' . curl_error($ch));
            exit;
        }
        curl_close($ch);

        // Save response to log
        if (is_dir(ROOT_PATH . '/cache/paypal_logs/')) {
            if (isset($_POST['payment_status'])) {
                file_put_contents(ROOT_PATH . '/cache/paypal_logs/'.$this->getName() . '_' . $_POST['payment_status'] .'_'.date('U').'.txt', $req);
            } else {
                file_put_contents(ROOT_PATH . '/cache/paypal_logs/'.$this->getName() . '_no_event_'.date('U').'.txt', $req);
            }
        }

        if (strcmp($res, "VERIFIED") == 0) {
            $receiver_email = $_POST['receiver_email'];

            $item_name = $_POST['item_name'];
            $item_number = $_POST['item_number'];
            $payment_status = $_POST['payment_status'];
            $payment_amount = Store::toCents($_POST['mc_gross']);
            $payment_currency = $_POST['mc_currency'];
            $payment_fee = $_POST['mc_fee'];
            $transaction_id = $_POST['txn_id'];
            $payer_email = $_POST['payer_email'];
            $order_id = $_POST['custom'];

            $paypal_email = StoreConfig::get('paypal/email');

            if ($paypal_email == $receiver_email) {

                // Handle event
                switch ($payment_status) {
                    case 'Completed';
                        // Payment complete
                        $payment = new Payment($transaction_id, 'transaction');
                        if ($payment->exists()) {
                            // Payment exists
                            $data = [
                                'transaction' => $transaction_id,
                                'amount_cents' => $payment_amount,
                                'currency' => $payment_currency,
                                'fee_cents' => Store::toCents($payment_fee ?? 0)
                            ];
                        } else {
                            // Register new payment
                            $data = [
                                'order_id' => $order_id,
                                'gateway_id' => $this->getId(),
                                'transaction' => $transaction_id,
                                'amount_cents' => $payment_amount,
                                'currency' => $payment_currency,
                                'fee_cents' => Store::toCents($payment_fee ?? 0)
                            ];
                        }
                        
                        $payment->handlePaymentEvent(Payment::COMPLETED, $data);
                    break;
                    case 'Refunded';
                        // Payment refunded
                        $payment = new Payment($_POST['parent_txn_id'], 'transaction');
                        if ($payment->exists()) {
                            // Payment exists
                            $payment->handlePaymentEvent(Payment::REFUNDED);
                        }
                    break;
                    default:
                        // Payment refunded
                        $payment = new Payment($transaction_id, 'transaction');
                        if ($payment->exists()) {
                            // Payment exists
                            $payment->handlePaymentEvent(Payment::REFUNDED);
                        }
                    break;
                }

                echo 'success';
            } else {
                ErrorHandler::logWarning('[Store] [PayPal Gateway] Paypal email mismatch!');
                die('Error');
            }
        } else {
            ErrorHandler::logWarning('[Store] [PayPal Gateway] Could not verify payment!');
            die('Error');
        }
    }
}

$gateway = new PayPal_Gateway();
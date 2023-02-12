<?php
class EmailService extends ServiceBase {
    public function __construct() {
        $id = 5;
        $name = 'Email';
        $description = 'With this action you can send email to your customer';
        $connection_settings = null;
        $action_settings = ROOT_PATH . '/modules/Store/services/Email/settings/action_settings.php';

        parent::__construct($id, $name, $description, $connection_settings, $action_settings);
    }

    public function onConnectionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function executeAction(Action $action, Order $order, Product $product, Payment $payment, array $placeholders) {
        $user = $order->recipient()->getUser();
        if ($user->exists()) {
            $email = json_decode($action->data()->command, true);

            // Replace the email placeholders
            $content = $action->data()->command;
            $content = str_replace(array_keys($placeholders), array_values($placeholders), $email['content']);

            $sent = Email::send(
                ['email' => $user->data()->email, 'name' => SITE_NAME],
                $email['subject'],
                $content,
                Email::getReplyTo()
            );

            // Action executed
            DB::getInstance()->insert('store_pending_actions', [
                'order_id' => $payment->data()->order_id,
                'action_id' => $action->data()->id,
                'product_id' => $product->data()->id,
                'customer_id' => $order->data()->to_customer_id,
                'connection_id' => 0,
                'type' => $action->data()->type,
                'command' =>  json_encode(['subject' => $email['subject'], 'content' => $content]),
                'require_online' => $action->data()->require_online,
                'order' => $action->data()->order,
                'status' => 1
            ]);
        }
    }
}

$service = new EmailService();
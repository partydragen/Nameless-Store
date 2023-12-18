<?php
class NamelessMCService extends ServiceBase {
    public function __construct() {
        $id = 1;
        $name = 'NamelessMC';
        $description = 'With this action you can reward your NamelessMC User with groups and credits (Must be registered on website)';
        $connection_settings = null;
        $action_settings = ROOT_PATH . '/modules/Store/services/NamelessMC/settings/action_settings.php';

        parent::__construct($id, $name, $description, $connection_settings, $action_settings);
    }

    public function onConnectionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function executeAction(Action $action, Order $order, Product $product, Payment $payment, array $placeholders) {
        $recipient = $order->recipient();
        if ($recipient->exists() && $recipient->getUser()->exists()) {
            // Get original recipient user
            $recipient = new Customer($recipient->getUser());
            $user = $recipient->getUser();
            $command = json_decode($action->data()->command, true);

            // Add groups to user
            if (isset($command['add_groups']) && is_array($command['add_groups']) && count($command['add_groups'])) {
                foreach ($command['add_groups'] as $group) {
                    $user->addGroup($group);
                }
            }

            // Remove groups from user
            if (isset($command['remove_groups']) && is_array($command['remove_groups']) && count($command['remove_groups'])) {
                foreach ($command['remove_groups'] as $group) {
                    $user->removeGroup($group);
                }
            }

            // Add credits to user
            if (isset($command['add_credits']) && is_numeric($command['add_credits']) && $command['add_credits'] > 0) {
                $recipient->addCents(Store::toCents($command['add_credits']));
            }

            // Remove credits from user
            if (isset($command['remove_credits']) && is_numeric($command['remove_credits']) && $command['remove_credits'] > 0) {
                $recipient->removeCents(Store::toCents($command['remove_credits']));
            }

            // Send alert to user
            if (isset($command['alert']) && !empty($command['alert'])) {
                $alert = str_replace(array_keys($placeholders), array_values($placeholders), $command['alert']);
                DB::getInstance()->insert('alerts', [
                    'user_id' => $user->data()->id,
                    'type' => 'store',
                    'url' => URL::build('/user/alerts'),
                    'content_short' => $alert,
                    'content' => $alert,
                    'created' => date('U')
                ]);
            }

            // Add trophies to user
            if (isset($command['add_trophies']) && is_array($command['add_trophies']) && count($command['add_trophies']) && Util::isModuleEnabled('Trophies')) {
                $user_trophies = new UserTrophies($user);

                foreach ($command['add_trophies'] as $trophy) {
                    $trophy = new Trophy($_POST['trophy']);
                    if ($trophy->exists()) {
                        if (!$user_trophies->hasTrophy($trophy)) {
                            $user_trophies->rewardTrophy($trophy);
                        }
                    }
                }
            }
        }

        // Action executed
        DB::getInstance()->insert('store_pending_actions', [
            'order_id' => $payment->data()->order_id,
            'action_id' => $action->data()->id,
            'product_id' => $product->data()->id,
            'customer_id' => $order->data()->to_customer_id,
            'connection_id' => 0,
            'type' => $action->data()->type,
            'command' => $action->data()->command,
            'require_online' => $action->data()->require_online,
            'order' => $action->data()->order,
            'status' => 1
        ]);
    }
}

$service = new NamelessMCService();
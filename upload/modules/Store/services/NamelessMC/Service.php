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

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function scheduleAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        $product = $item->getProduct();
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

            if ((isset($command['add_groups']) && count($command['add_groups'])) || (isset($command['remove_groups']) && count($command['remove_groups']))) {
                GroupSyncManager::getInstance()->broadcastChange(
                    $user,
                    NamelessMCGroupSyncInjector::class,
                    $user->getAllGroupIds(),
                );
            }

            // Add credits to user
            if (isset($command['add_credits']) && is_numeric($command['add_credits']) && $command['add_credits'] > 0) {
                $recipient->addCents(Store::toCents($command['add_credits']), 'Product Action');
            }

            // Remove credits from user
            if (isset($command['remove_credits']) && is_numeric($command['remove_credits']) && $command['remove_credits'] > 0) {
                $recipient->removeCents(Store::toCents($command['remove_credits']), 'Product Action');
            }

            // Send alert to user
            if (isset($command['alert']) && !empty($command['alert'])) {
                $alert = $action->parseCommand($command['alert'], $order, $item, $payment, $placeholders);

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

                foreach ($command['add_trophies'] as $trophy_id) {
                    $trophy = new Trophy($trophy_id);
                    if ($trophy->exists()) {
                        if (!$user_trophies->hasTrophy($trophy)) {
                            $user_trophies->rewardTrophy($trophy);
                        }
                    }
                }
            }
        }

        $task = new ActionTask();
        $task->create($action->data()->command, $action, $order, $item, $payment, [
            'connection_id' => 0,
            'status' => ActionTask::COMPLETED
        ]);
    }

    public function executeAction(ActionTask $task) {

    }
}

$service = new NamelessMCService();
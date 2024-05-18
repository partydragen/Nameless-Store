<?php
class DiscordService extends ServiceBase {
    public function __construct() {
        $id = 6;
        $name = 'Discord';
        $description = 'Reward discord roles & Sending webhook message';
        $connection_settings = null;
        $action_settings = ROOT_PATH . '/modules/Store/services/Discord/settings/action_settings.php';

        parent::__construct($id, $name, $description, $connection_settings, $action_settings);
    }

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function executeAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        $command = json_decode($action->data()->command, true);

        // Add or Remove roles
        if (isset($command['add_roles']) || isset($command['remove_roles'])) {
            $recipient = $order->recipient();
            if ($recipient->exists() && $recipient->getUser()->exists()) {
                // Get original recipient user
                $user = $recipient->getUser();

                Discord::updateDiscordRoles($user, $command['add_roles'] ?? [], $command['remove_roles'] ?? []);
            }
        }

        // Webhook
        if (isset($command['webhook']['url'])) {
            $webhook = $command['webhook'];

            $return = DiscordWebhookBuilder::make()
                ->setContent(str_replace(array_keys($placeholders), array_values($placeholders), $webhook['content']));

            // Any embeds?
            if (isset($webhook['embeds'])) {
                foreach ($webhook['embeds'] as $params) {
                    if (empty($params['description'])) {
                        continue;
                    }

                    $return->addEmbed(function (DiscordEmbed $embed) use ($params, $placeholders) {
                        return $embed
                            ->setTitle(str_replace(array_keys($placeholders), array_values($placeholders), $params['title']))
                            ->setDescription(Text::embedSafe(str_replace(array_keys($placeholders), array_values($placeholders), $params['description'])))
                            ->setUrl($params['url'])
                            ->setFooter(Text::embedSafe(str_replace(array_keys($placeholders), array_values($placeholders), $params['footer']['text'])));
                    });
                }
            }

            $json = json_encode($return->toArray(), JSON_UNESCAPED_SLASHES);

            HttpClient::post($webhook['url'], $json, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);
        }

        // Action executed
        DB::getInstance()->insert('store_pending_actions', [
            'order_id' => $payment->data()->order_id,
            'action_id' => $action->data()->id,
            'product_id' => $item->getProduct()->data()->id,
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

$service = new DiscordService();
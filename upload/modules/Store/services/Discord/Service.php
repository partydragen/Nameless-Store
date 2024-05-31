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

    public function scheduleAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        $command = json_decode($action->data()->command, true);
        $command_log = [];

        // Add or Remove roles
        if (isset($command['add_roles']) || isset($command['remove_roles'])) {
            $recipient = $order->recipient();
            if ($recipient->exists() && $recipient->getUser()->exists()) {
                // Get original recipient user
                $user = $recipient->getUser();

                Discord::updateDiscordRoles($user, $command['add_roles'] ?? [], $command['remove_roles'] ?? []);
            }

            if (isset($command['add_roles'])) {
                $command_log['add_roles'] = $command['add_roles'];
            }

            if (isset($command['remove_roles'])) {
                $command_log['remove_roles'] = $command['remove_roles'];
            }
        }

        // Webhook
        if (isset($command['webhook']['url'])) {
            $webhook = $command['webhook'];

            $content = $action->parseCommand($webhook['content'], $order, $item, $payment, $placeholders);
            $return = DiscordWebhookBuilder::make()
                ->setContent($content);

            // Any embeds?
            if (isset($webhook['embeds'])) {
                foreach ($webhook['embeds'] as $params) {
                    if (empty($params['description'])) {
                        continue;
                    }

                    $title = $action->parseCommand($params['title'], $order, $item, $payment, $placeholders);
                    $description = $action->parseCommand($params['description'], $order, $item, $payment, $placeholders);
                    $footer = $action->parseCommand($params['footer']['text'], $order, $item, $payment, $placeholders);

                    $return->addEmbed(function (DiscordEmbed $embed) use ($params, $title, $description, $footer) {
                        return $embed
                            ->setTitle($title)
                            ->setDescription(Text::embedSafe($description))
                            ->setUrl($params['url'])
                            ->setFooter(Text::embedSafe($footer));
                    });
                }
            }

            $json = json_encode($return->toArray(), JSON_UNESCAPED_SLASHES);

            HttpClient::post($webhook['url'], $json, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $command_log['webhook'] = [
                'url' => $command['webhook']['url'],
                'content' => $content,
                'embeds' => [
                    [
                        'title' => $title ?? '',
                        'description' => $description ?? '',
                        'footer' => [
                            'text' => $footer ?? '',
                        ]
                    ]
                ],
            ];
        }
        $command = json_encode($command_log);

        $task = new ActionTask();
        $task->create($command, $action, $order, $item, $payment, [
            'connection_id' => 0,
            'status' => ActionTask::COMPLETED
        ]);
    }

    public function executeAction(ActionTask $task) {

    }
}

$service = new DiscordService();
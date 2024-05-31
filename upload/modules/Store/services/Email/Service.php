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

    public function onActionSettingsPageLoad(TemplateBase $template, Fields $fields) {

    }

    public function scheduleAction(Action $action, Order $order, Item $item, Payment $payment, array $placeholders) {
        $product = $item->getProduct();
        $user = $order->recipient()->getUser();
        if ($user->exists()) {
            $email = json_decode($action->data()->command, true);

            // Replace the email placeholders
            $content = $action->parseCommand($email['content'], $order, $item, $payment, $placeholders);

            $sent = Email::send(
                ['email' => $user->data()->email, 'name' => SITE_NAME],
                $email['subject'],
                $content,
                Email::getReplyTo()
            );

            $command = json_encode(['subject' => $email['subject'], 'content' => $content]);

            $task = new ActionTask();
            $task->create($command, $action, $order, $item, $payment, [
                'connection_id' => 0,
                'status' => ActionTask::COMPLETED
            ]);
        }
    }

    public function executeAction(ActionTask $task) {

    }
}

$service = new EmailService();
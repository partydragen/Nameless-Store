<?php

class ParseActionCommandListener extends HookBase {

    private static ParseActionCommandEvent $_event;

    public static function placeholders(ParseActionCommandEvent $event): void {
        $placeholders = ActionsHandler::getInstance()->getPlaceholders($event->action, $event->order, $event->item, $event->payment);
        $event->command = str_replace(array_keys($placeholders), array_values($placeholders), $event->command);

        /*$command = preg_replace_callback(
            '/{(.*?)}/ism',
            static function (array $match) {
                $value = ActionsHandler::getInstance()->getPlaceholder(str_replace('$', '', $condition), $event->action, $event->order, $event->item, $event->payment);
            },
            $event->command
        );*/
    }

    public static function conditions(ParseActionCommandEvent $event): void {
        self::$_event = $event;

        $command = preg_replace_callback(
            '/{if (.*?)}(.*?){\/if}/ism',
            static function (array $match) {
                $condition = self::hasCondition($match[1]);

                return $condition ? $match[2] : '';
            },
            $event->command
        );

        $event->command = $command;
    }

    private static function hasCondition($condition): bool {
        $event = self::$_event;
        $placeholder = str_replace('$', '', $condition);

        $value = ActionsHandler::getInstance()->getPlaceholder(str_replace('$', '', $condition), $event->action, $event->order, $event->item, $event->payment);
        if ($value == 'true') {
            return true;
        }

        if (!empty($value) && $value != 'false' && $value != $placeholder) {
            return true;
        }

        return false;
    }
}
<?php

class ParseActionCommandListener extends HookBase {

    public static function placeholders(ParseActionCommandEvent $event): void {
        $event->command = preg_replace_callback(
            '/{(.*?)}/ism',
            static function (array $match) use($event) {
                $value = $event->getPlaceholder($match[1]);
                if ($value !== null) {
                    return $value;
                }

                return $match[0];
            },
            $event->command
        );
    }

    public static function conditions(ParseActionCommandEvent $event): void {
        $event->command = preg_replace_callback(
            '/{if \$(.*?)}(.*?){\/if}/ism',
            static function (array $match) use($event) {
                $condition = self::hasCondition($match[1], $event);

                return $condition ? $match[2] : '';
            },
            $event->command
        );
    }

    private static function hasCondition($placeholder, $event): bool {
        $value =  $event->getPlaceholder($placeholder);
        if ($value == 'true') {
            return true;
        }

        if (!empty($value) && $value != 'false' && $value != $placeholder) {
            return true;
        }

        return false;
    }
}
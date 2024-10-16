<?php

class PlaceholderCondition extends ConditionBase
{

    public function name(): string
    {
        return 'placeholder';
    }

    public function getSelectionFields(): Fields
    {
        $fields = new Fields();

        $fields->add('placeholder', Fields::SELECT, 'Placeholder');
        $fields->addOption('placeholder', 'tntwars_wins', 'tntwars_wins');
        $fields->addOption('placeholder', 'tntwars_kills', 'tntwars_kills');
        $fields->addOption('placeholder', 'tntwars_score', 'tntwars_score');

        $fields->add('type', Fields::SELECT, 'Type');
        $fields->addOption('type', '=', '=');
        $fields->addOption('type', '<', '<');
        $fields->addOption('type', '>', '>');
        $fields->addOption('type', '!=', '!=');

        $fields->add('value', Fields::TEXT, 'Value');

        return $fields;
    }

    public function hasCondition(): bool
    {
        return true;
    }
}
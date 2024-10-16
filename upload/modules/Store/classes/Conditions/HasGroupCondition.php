<?php

class HasGroupCondition extends ConditionBase
{

    public function name(): string
    {
        return 'hasGroup';
    }

    public function getSelectionFields(): Fields
    {
        $fields = new Fields();

        $fields->add('group', Fields::SELECT, 'Group');

        $groups = DB::getInstance()->query('SELECT * FROM nl2_groups')->results();
        foreach ($groups as $item) {
            $fields->addOption('group', $item->id, $item->name);
        }

        return $fields;
    }

    public function hasCondition(): bool
    {
        return true;
    }
}
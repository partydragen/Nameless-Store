<?php

class HasIntegrationCondition extends ConditionBase
{

    public function name(): string
    {
        return 'hasIntegration';
    }

    public function getSelectionFields(): Fields
    {
        $fields = new Fields();

        $fields->add('integration', Fields::SELECT, 'Integration');
        foreach (Integrations::getInstance()->getEnabledIntegrations() as $item) {
            $fields->addOption('integration', $item->getName(), $item->getName());
        }

        return $fields;
    }

    public function hasCondition(): bool
    {
        return true;
    }
}
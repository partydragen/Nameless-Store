<?php

class Conditions extends Instanceable
{
    /**
     * @var ConditionBase[] The list of conditions.
     */
    private array $_conditions = [];

    public function registerCondition(ConditionBase $condition): void
    {
        $this->_conditions[] = $condition;
    }

    public function getConditions(): array {
        return $this->_conditions;
    }
}
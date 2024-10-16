<?php

abstract class ConditionBase {

    abstract public function name(): string;

    abstract public function getSelectionFields(): Fields;

    abstract public function hasCondition(): bool;
}
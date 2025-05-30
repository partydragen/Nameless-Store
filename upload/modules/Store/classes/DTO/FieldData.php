<?php

class FieldData {

    public int $id;
    public string $identifier;
    public string $description;
    public int $type;
    public bool $required;
    public int $min;
    public int $max;
    public array $options = [];
    public ?string $regex;
    public string $default_value;
    public int $deleted;
    public int $order;

    public function __construct(object $row) {
        $this->id = $row->id;
        $this->identifier = $row->identifier;
        $this->description = $row->description;
        $this->type = $row->type;
        $this->required = $row->required;
        $this->min = $row->min;
        $this->max = $row->max;
        $this->regex = $row->regex;
        $this->default_value = $row->default_value;
        $this->deleted = $row->deleted;
        $this->order = $row->order;

        // TODO: Rework the way options are stored in database to add support for value, description and price
        foreach (explode(',', Output::getClean($row->options)) as $option) {
            $this->addOption($option, $option);
        }
    }

    /**
     * Add option to field
     *
     * @param string $value       Option value.
     * @param string $description The option description to display.
     */
    public function addOption(string $value, string $description, int $price_cents = null): void {
        $this->options[] = [
            'value' => $value,
            'description' => $description,
            'price' => $price_cents
        ];
    }

}
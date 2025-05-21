<?php

class FieldData {

    public int $id;
    public string $identifier;
    public string $description;
    public int $type;
    public bool $required;
    public int $min;
    public int $max;
    public array $options;
    public array $selections;
    public ?string $regex;
    public string $default_value;
    public int $deleted;
    public int $order;

    public function __construct(object $row) {
        $selections = [];
        foreach (explode(',', Output::getClean($row->options)) as $option) {
            $selections[] = [
                'value' => $option,
                'description' => $option,
                'price' => null,
            ];
        }

        $this->id = $row->id;
        $this->identifier = $row->identifier;
        $this->description = $row->description;
        $this->type = $row->type;
        $this->required = $row->required;
        $this->min = $row->min;
        $this->max = $row->max;
        $this->options = explode(',', Output::getClean($row->options));
        $this->selections = $selections;
        $this->regex = $row->regex;
        $this->default_value = $row->default_value;
        $this->deleted = $row->deleted;
        $this->order = $row->order;
    }

}
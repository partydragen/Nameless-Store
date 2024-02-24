<?php

class ProductData {

    public int $id;
    public int $category_id;
    public string $name;
    public int $price_cents;
    public ?string $description;
    public ?string $image;
    public ?string $durability;
    public ?string $global_limit;
    public ?string $user_limit;
    public ?string $required_products;
    public ?string $required_groups;
    public ?string $required_integrations;
    public bool $require_one_product;
    public ?string $allowed_gateways;
    public ?string $min_player_age;
    public ?string $min_player_playtime;
    public int $payment_type;
    public bool $hidden;
    public bool $disabled;
    public int $order;
    public int $deleted;
    public bool $sale_active = false;
    public int $sale_discount_cents = 0;

    public function __construct(object $row) {
        $this->id = $row->id;
        $this->category_id = $row->category_id;
        $this->name = $row->name;
        $this->price_cents = $row->price_cents;
        $this->description = $row->description;
        $this->image = $row->image;
        $this->durability = $row->durability;
        $this->global_limit = $row->global_limit;
        $this->user_limit = $row->user_limit;
        $this->required_products = $row->required_products;
        $this->required_groups = $row->required_groups;
        $this->required_integrations = $row->required_integrations;
        $this->require_one_product = $row->require_one_product;
        $this->allowed_gateways = $row->allowed_gateways;
        $this->min_player_age = $row->min_player_age;
        $this->min_player_playtime = $row->min_player_playtime;
        $this->payment_type = $row->payment_type;
        $this->hidden = $row->hidden;
        $this->disabled = $row->disabled;
        $this->order = $row->order;
        $this->deleted = $row->deleted;
    }

}
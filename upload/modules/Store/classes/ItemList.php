<?php
/**
 * ItemList class.
 *
 * @package Modules\Store
 * @author Partydragen
 * @version 2.2.0
 * @license MIT
 */
class ItemList {

    /**
     * @var Item[] The list of items.
     */
    private array $_items = [];

    /**
     * Get the items for this order.
     *
     * @return Item[] The items for this order.
     */
    public function getItems(): array {
        return $this->_items;
    }

    public function addItem(Item $item) {
        $this->_items[] = $item;
    }
}
<?php
class Order {
    private $database;

    public function __construct(DB $database) {
        $this->database = $database;
    }

    public function getOrdersByUserId($userId) {
        $userId = $this->database->escape($userId);

        return $this->database->query("
        SELECT cart.item_id, cart.quantity, cart.total_price, items.name as item_name, items.price as unit_price 
        FROM cart 
        INNER JOIN items ON cart.item_id = items.id 
        WHERE cart.user_id = '{$userId}'
        ")->rows;
    }
}
<?php

class Item {
    private $database;

    public function __construct(DB $database) {
        $this->database = $database;
    }

    public function getAll() {
        return $this->database->query("SELECT * FROM items");
    }
}
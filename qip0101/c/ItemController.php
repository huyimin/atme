<?php
class ItemController {
    private $itemModel;

    public function __construct(Item $itemModel) {
        $this->itemModel = $itemModel;
    }

    public function index() {
        $items = $this->itemModel->getAll()->rows;
        return $items;
    }
}
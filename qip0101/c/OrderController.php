<?php
class OrderController {
    private $orderModel;

    public function __construct(Order $orderModel) {
        $this->orderModel = $orderModel;
    }

    public function getOrders() {
        // Assume that the user ID is stored in the session.
        return $this->orderModel->getOrdersByUserId($_SESSION['user_id']);
    }
}
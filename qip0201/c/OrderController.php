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

    public function getFinalOrdersByUserId($userId) {
        return $this->orderModel->getFinalOrdersByUserId($userId);
    }

    public function sell($userId, $itemId, $quantityToSell) {
        return $this->orderModel->sellItem($userId, $itemId, $quantityToSell);
    }

    public function return($userId, $itemId, $quantityToReturn) {
        return $this->orderModel->returnItem($userId, $itemId, $quantityToReturn);
    }

    public function finalizeDeal($itemIds) {
        $userId = $_SESSION['user_id'];
        return $this->orderModel->finalizeDeal($userId, $itemIds);
    }
}
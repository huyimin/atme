<?php
class CartController {
    private $cartModel;

    public function __construct(Cart $cartModel) {
        $this->cartModel = $cartModel;
    }

    public function checkout() {
        $data = json_decode(file_get_contents('php://input'), true);
        // TODO: Validate the data.
        $result = $this->cartModel->checkout($data);
        if ($result['success']) {
            echo json_encode(['success' => true, 'count' => $result['totalItemCount']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'An error occurred during checkout']);
        }
    }

    public function updateItemQuantity() {
        $data = json_decode(file_get_contents('php://input'), true);
        $itemId = $data['itemId'];
        $quantity = $data['quantity'];
        $userId = $_SESSION['user_id'];

        if ($quantity > 0) {
            $success = $this->cartModel->updateItemQuantity($userId, $itemId, $quantity);
        } else {
            $success = $this->cartModel->removeItem($userId, $itemId);
        }

        echo json_encode(['success' => $success]);
    }

    public function confirmOrder() {
        $userId = $_SESSION['user_id'];
        $result = $this->cartModel->createOrderAndClearCart($userId);

        echo json_encode($result);
    }

    public function removeItem() {
        // This method can be simplified or merged with updateItemQuantity if they perform the same action
        $data = json_decode(file_get_contents('php://input'), true);
        $itemId = $data['itemId'];
        $userId = $_SESSION['user_id'];

        $success = $this->cartModel->removeItem($userId, $itemId);

        echo json_encode(['success' => $success]);
    }
}
<?php
class Cart {
    private $db;

    public function __construct(DB $db) {
        $this->db = $db;
    }

    public function checkout($data) {
        if (!isset($data['cart'])) {
            return false;
        }

        $userId = $_SESSION['user_id'];

        // Start the transaction
        $this->db->query("START TRANSACTION");

        try {
            foreach ($data['cart'] as $itemId => $itemData) {
                $itemId = $this->db->escape($itemId);
                $itemQuantity = $this->db->escape($itemData['quantity']);
                $itemPrice = $this->db->escape($itemData['price']);
                $totalPrice = $itemPrice * $itemQuantity;

                // Check for existing cart item
                $existingItemQuery = $this->db->query("SELECT quantity FROM cart WHERE user_id = '$userId' AND item_id = '$itemId'");
                if ($existingItemQuery->num_rows > 0) {
                    $existingItem = $existingItemQuery->row;
                    $newQuantity = $existingItem['quantity'] + $itemQuantity;
                    // Update total price calculation to include updated quantity
                    $newTotalPrice = $itemPrice * $newQuantity;
                    $this->db->query("UPDATE cart SET quantity = '$newQuantity', total_price = '$newTotalPrice' WHERE user_id = '$userId' AND item_id = '$itemId'");
                } else {
                    // Insert a new row if the item does not exist
                    $this->db->query("INSERT INTO cart (user_id, item_id, quantity, order_id, total_price) VALUES ('$userId', '$itemId', '$itemQuantity', UUID(), '$totalPrice')");
                }

                if ($this->db->countAffected() <= 0) {
                    throw new Exception('Failed to insert or update item into cart.');
                }
            }

            // After adding items to cart, get the total item count
            $totalItemCount = $this->getTotalItemCount($userId);

            // If everything is fine, commit the transaction
            $this->db->query("COMMIT");

                return ['success' => true, 'totalItemCount' => $totalItemCount];
            } catch (Exception $e) {
                // If there is any error, rollback the transaction
                $this->db->query("ROLLBACK");
                return ['success' => false, 'totalItemCount' => 0];
            }
    }

    private function getTotalItemCount($userId) {
        $userId = $this->db->escape($userId);
        $result = $this->db->query("SELECT SUM(quantity) AS total_quantity FROM cart WHERE user_id = '$userId'");
        return $result->row['total_quantity'] ?? 0;
    }

    public function updateItemQuantity($userId, $itemId, $quantity) {
        $userId = $this->db->escape($userId);
        $itemId = $this->db->escape($itemId);
        $quantity = $this->db->escape($quantity);

        if ($quantity <= 0) {
            // If quantity is zero or less, remove the item.
            return $this->removeItem($userId, $itemId);
        } else {
            // Update item quantity in the cart.
            $this->db->query("UPDATE cart SET quantity = '$quantity' WHERE user_id = '$userId' AND item_id = '$itemId'");
            return $this->db->countAffected() > 0;
        }
    }

    public function createOrderAndClearCart($userId) {
        $this->db->query("START TRANSACTION");

        try {
            $cartItems = $this->db->query("SELECT item_id, quantity, items.price, items.name AS item_name
                                        FROM cart
                                        JOIN items ON cart.item_id = items.id
                                        WHERE cart.user_id = '$userId'")->rows;
            foreach ($cartItems as $item) {
                $orderNumber = $userId . date('YmdHis');
                $this->db->query("INSERT INTO orders (order_number, user_id, item_id, item_name, unit_price, quantity, stock) VALUES ('$orderNumber', '$userId', '{$item['item_id']}', '{$item['item_name']}', '{$item['price']}', '{$item['quantity']}', '{$item['quantity']}')");
                if ($this->db->countAffected() <= 0) {
                    error_log('Failed to insert order for user: ' . $userId);
                    throw new Exception('Failed to create order.');
                }
            }

            // Clear the cart
            $this->db->query("DELETE FROM cart WHERE user_id = '$userId'");
            if ($this->db->countAffected() <= 0 && count($cartItems) > 0) {
                error_log('Failed to clear cart for user: ' . $userId);
                throw new Exception('Failed to clear cart.');
            }

            $this->db->query("COMMIT");
            return ['success' => true, 'message' => 'Order created and cart cleared'];
        } catch (Exception $e) {
            $this->db->query("ROLLBACK");
            error_log('Transaction failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function removeItem($userId, $itemId) {
        $userId = $this->db->escape($userId);
        $itemId = $this->db->escape($itemId);

        $this->db->query("DELETE FROM cart WHERE user_id = '$userId' AND item_id = '$itemId'");
        return $this->db->countAffected() > 0;
    }
}
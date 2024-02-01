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

    public function getFinalOrdersByUserId($userId) {
        $userId = $this->database->escape($userId);

        return $this->database->query("
            SELECT id, order_number, item_id, item_name, unit_price, quantity, sold, stock, returned, order_time 
            FROM orders 
            WHERE user_id = '{$userId}'
            ORDER BY order_time DESC
        ")->rows;
    }

    public function sellItem($userId, $itemId, $quantityToSell) {
        $this->database->query("START TRANSACTION");
        
        try {
            $orders = $this->database->query("SELECT * FROM orders WHERE user_id = '{$userId}' AND item_id = '{$itemId}' ORDER BY order_time ASC")->rows;
            
            foreach ($orders as $order) {
                if ($quantityToSell <= 0) break;
                
                $sellQuantity = min($order['stock'], $quantityToSell);
                $newSold = $order['sold'] + $sellQuantity;
                $newStock = $order['stock'] - $sellQuantity;
                $quantityToSell -= $sellQuantity;
                
                $this->database->query("UPDATE orders SET sold = '$newSold', stock = '$newStock' WHERE id = '{$order['id']}'");
                $updatedOrder = $this->database->query("SELECT sold, stock FROM orders WHERE item_id = '{$itemId}' AND user_id = '{$userId}'")->row;
                $updatedSold = $updatedOrder['sold'];
                $updatedStock = $updatedOrder['stock'];
            }
            $this->database->query("COMMIT");
            return [
                'success' => true,
                'message' => 'Operation successful.',
                'updatedSold' => $updatedSold,
                'updatedStock' => $updatedStock
            ];
        } catch (Exception $e) {
            $this->database->query("ROLLBACK");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function returnItem($userId, $itemId, $quantityToReturn) {
        $this->database->query("START TRANSACTION");
        
        try {
            $orders = $this->database->query("SELECT * FROM orders WHERE user_id = '{$userId}' AND item_id = '{$itemId}' ORDER BY order_time ASC")->rows;
            
            foreach ($orders as $order) {
                if ($quantityToReturn <= 0) break;
                
                $returnQuantity = min(($order['quantity'] - $order['returned'] - $order['sold']), $quantityToReturn);
                $newReturned = $order['returned'] + $returnQuantity;
                $newStock = $order['stock'] - $returnQuantity;
                $quantityToReturn -= $returnQuantity;
                
                $this->database->query("UPDATE orders SET returned = '$newReturned', stock = '$newStock' WHERE id = '{$order['id']}'");
                $updatedOrder = $this->database->query("SELECT returned, stock FROM orders WHERE item_id = '{$itemId}' AND user_id = '{$userId}'")->row;
                $updatedReturn = $updatedOrder['returned'];
                $updatedStock = $updatedOrder['stock'];
            }
            $this->database->query("COMMIT");
            return [
                'success' => true,
                'message' => 'Operation successful.',
                'updatedReturn' => $updatedReturn,
                'updatedStock' => $updatedStock
            ];
        } catch (Exception $e) {
            $this->database->query("ROLLBACK");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function finalizeDeal($userId, $itemIds) {
        $this->database->query("START TRANSACTION");

        try {
            $totalSold = 0;
            $clearanceAmount = 0;

            foreach ($itemIds as $itemId) {
                // Calculate the total sold and clearance amount
                $item = $this->database->query("SELECT SUM(sold) as totalSold, unit_price FROM orders WHERE item_id = '{$itemId}' AND user_id = '{$userId}' GROUP BY unit_price")->row;
                $totalSold += $item['totalSold'];
                $clearanceAmount += $item['totalSold'] * $item['unit_price'];
            }

            // Insert into the clearance table
            $this->database->query("INSERT INTO clearance (user_id, total_sold, clearance_amount) VALUES ('{$userId}', '{$totalSold}', '{$clearanceAmount}')");

            $this->database->query("COMMIT");
            return ['success' => true, 'message' => 'Deal has been finalized successfully.', 'clearanceId' => $this->database->getLastId()];
        } catch (Exception $e) {
            $this->database->query("ROLLBACK");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
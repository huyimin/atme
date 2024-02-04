<?php
class Order {
    private $database;

    public function __construct(DB $database) {
        $this->database = $database;
    }

    public function isUserAdmin($userId) {
        $userId = $this->database->escape($userId);
        $result = $this->database->query("
            SELECT username
            FROM users
            WHERE id = '{$userId}'
        ");
        // Check if the username is 'admin'
        return $result && $result->row && $result->row['username'] === 'admin';
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
                $query = "SELECT SUM(sold) as totalSold, unit_price FROM orders WHERE item_id = '{$itemId}' AND user_id = '{$userId}' AND sold > 0 GROUP BY unit_price";
                $result = $this->database->query($query);
                if ($result && $result->num_rows > 0) {
                    $item = $result->row; // Assuming that $result->row gives you the first row
                    $totalSold += $item['totalSold'];
                    $clearanceAmount += $item['totalSold'] * $item['unit_price'];
                }
            }

            // Insert into the clearance table or update if the user_id already exists
            $insertOrUpdateQuery = "INSERT INTO clearance (user_id, total_sold, clearance_amount) VALUES ('{$userId}', '{$totalSold}', '{$clearanceAmount}')
                                    ON DUPLICATE KEY UPDATE 
                                    total_sold = VALUES(total_sold),
                                    clearance_amount = VALUES(clearance_amount),
                                    created_at = CURRENT_TIMESTAMP";
            $this->database->query($insertOrUpdateQuery);

            $this->database->query("COMMIT");
            return ['success' => true, 'message' => 'Deal has been finalized successfully.', 'clearanceId' => $this->database->getLastId()];
        } catch (Exception $e) {
            $this->database->query("ROLLBACK");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getCurrentClearanceByUserId($userId) {
        $userId = $this->database->escape($userId);
        
        $result = $this->database->query("
            SELECT total_sold, clearance_amount 
            FROM clearance 
            WHERE user_id = '{$userId}'
        ");
        
        return $result ? $result->row : null;
    }

    // public function confirmInvoiceAndClear($userId) {
    //     $this->database->query("START TRANSACTION");

    //     try {
    //         // Fetch clearance information
    //         $clearance = $this->getCurrentClearanceByUserId($userId);
    //         if (!$clearance) {
    //             throw new Exception('No clearance data found.');
    //         }

    //         // Fetch sold items details to be stored in JSON format
    //         $soldItemsDetails = $this->getFinalOrdersByUserId($userId);
    //         // Convert the details to a JSON string
    //         $soldItemsJson = json_encode($soldItemsDetails);

    //         // Insert into clearance_history with sold_items details
    //         $this->database->query("INSERT INTO clearance_history (user_id, total_sold, clearance_amount, sold_items)
    //                                 VALUES ('{$userId}', '{$clearance['total_sold']}', '{$clearance['clearance_amount']}', '{$soldItemsJson}')");

    //         // Update orders: reset sold and returned, set quantity back to stock
    //         $this->database->query("UPDATE orders SET sold = 0, returned = 0, quantity = stock WHERE user_id = '{$userId}'");

    //         // Delete from clearance
    //         $this->database->query("DELETE FROM clearance WHERE user_id = '{$userId}'");

    //         $this->database->query("COMMIT");
    //         return ['success' => true, 'message' => 'Invoice confirmed and history updated.'];
    //     } catch (Exception $e) {
    //         $this->database->query("ROLLBACK");
    //         return ['success' => false, 'message' => $e->getMessage()];
    //     }
    // }

    public function getUnconfirmedInvoices() {
        return $this->database->query("
            SELECT *
            FROM clearance
        ")->rows;
    }

    // public function getOrdersByClearanceId($clearanceId) {
    //     // First, get the user_id and created_at timestamp of the clearance
    //     $clearanceInfo = $this->database->query("
    //         SELECT user_id, created_at
    //         FROM clearance
    //         WHERE id = '{$clearanceId}'
    //     ")->row;

    //     if (!$clearanceInfo) {
    //         throw new Exception('Clearance not found.');
    //     }

    //     // Fetch orders for the user that were created before the clearance timestamp
    //     $orders = $this->database->query("
    //         SELECT *
    //         FROM orders
    //         WHERE user_id = '{$clearanceInfo['user_id']}' AND order_time < '{$clearanceInfo['created_at']}'
    //     ")->rows;

    //     return $orders;
    // }

    public function confirmInvoiceById($invoiceId) {
        $this->database->query("START TRANSACTION");

        try {
            // Fetch clearance information by invoice ID
            $clearance = $this->database->query("
                SELECT user_id, total_sold, clearance_amount
                FROM clearance
                WHERE id = '{$invoiceId}'
            ")->row;

            if (!$clearance) {
                throw new Exception('No clearance data found for the given ID.');
            }

            // Fetch sold items details to be stored in JSON format
            $soldItemsDetails = $this->getFinalOrdersByUserId($clearance['user_id']);
            // Convert the details to a JSON string
            $soldItemsJson = json_encode($soldItemsDetails);

            // Insert into clearance_history with sold_items details
            $this->database->query("
                INSERT INTO clearance_history (user_id, total_sold, clearance_amount, sold_items)
                VALUES ('{$clearance['user_id']}', '{$clearance['total_sold']}', '{$clearance['clearance_amount']}', '{$soldItemsJson}')
            ");

            // Update orders: reset sold and returned, set quantity back to stock
            $this->database->query("
                UPDATE orders 
                SET sold = 0, returned = 0, quantity = stock 
                WHERE user_id = '{$clearance['user_id']}'
            ");

            // Delete from clearance
            $this->database->query("
                DELETE FROM clearance 
                WHERE id = '{$invoiceId}'
            ");

            $this->database->query("COMMIT");
            return ['success' => true, 'message' => 'Invoice confirmed.'];
        } catch (Exception $e) {
            $this->database->query("ROLLBACK");
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getClearanceHistoryByUserId($userId) {
        $userId = $this->database->escape($userId);

        return $this->database->query("
            SELECT *
            FROM clearance_history
            WHERE user_id = '{$userId}'
            ORDER BY created_at DESC
        ")->rows;
    }

    public function getAllUsers() {
        return $this->database->query("
            SELECT id, username
            FROM users
            ORDER BY username ASC
        ")->rows;
    }
    public function getAllClearanceHistories() {
        return $this->database->query("
            SELECT *
            FROM clearance_history
            ORDER BY created_at DESC
        ")->rows;
    }
}
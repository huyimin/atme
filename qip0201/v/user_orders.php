<html>
<body>
<?php if (!empty($orders)): ?>
    <h2>My Orders</h2>
    <table id="orders-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Number</th>
                <th>Item Name</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Sold</th>
                <th>Stock</th>
                <th>Returned</th>
                <th>Order Time</th>
                <th>Sell</th>
                <th>Return</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr data-item-id="<?php echo $order['item_id']; ?>">
                <td><?php echo $order['id']; ?></td>
                <td><?php echo $order['order_number']; ?></td>
                <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                <td><?php echo $order['unit_price']; ?></td>
                <td><?php echo $order['quantity']; ?></td>
                <td class="sold"><?php echo $order['sold']; ?></td>
                <td class="stock"><?php echo $order['stock']; ?></td>
                <td class="returned"><?php echo $order['returned']; ?></td>
                <td><?php echo $order['order_time']; ?></td>
                <td>
                    <input type="number" min="0" class="sell-quantity-input" data-item-id="<?php echo $order['item_id']; ?>" placeholder="Qty to sell">
                    <button class="sell-item-btn" data-item-id="<?php echo $order['item_id']; ?>" ... >Sell</button>
                </td>
                <td>
                    <input type="number" min="0" class="return-quantity-input" data-item-id="<?php echo $order['item_id']; ?>" placeholder="Qty to return">
                    <button class="return-item-btn" data-item-id="<?php echo $order['item_id']; ?>" ... >Return</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<button id="finalize-deal-btn">Finalize Deal</button>
<?php else: ?>
    <p>No orders found.</p>
<?php endif; ?>
<script src="/assets/js/order-actions.js"></script>
</body>
</html>
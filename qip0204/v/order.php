<?php if (!empty($orders)): ?>
    <h2>Orders</h2>
    <table id="orders-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                <td>
                    <button class="decrease-quantity" data-id="<?php echo $order['item_id']; ?>">-</button>
                    <span class="quantity" data-id="<?php echo $order['item_id']; ?>">
                        <?php echo $order['quantity']; ?>
                    </span>
                    <button class="increase-quantity" data-id="<?php echo $order['item_id']; ?>">+</button>
                </td>
                <td class="unit-price" data-id="<?php echo $order['item_id']; ?>">
                    <?php echo htmlspecialchars($order['unit_price']); ?>
                </td>
                <td class="total-price" data-id="<?php echo $order['item_id']; ?>">
                    <?php echo htmlspecialchars($order['total_price']); ?>
                </td>
                <td>
                    <button class="delete-item" data-id="<?php echo $order['item_id']; ?>">x</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="text-align: right;">
                    <button id="confirm-order-btn">Confirm Order</button>
                </td>
            </tr>
        </tfoot>
    </table>
<?php else: ?>
    <p>No orders found.</p>
<?php endif; ?>
<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Autoload classes
spl_autoload_register(function ($class_name) {
    require_once '../m/' . $class_name . '.php';
});

$db = new DB();

// Fetch clearance and orders data
$orderModel = new Order($db);
$userId = $_SESSION['user_id'];
$clearance = $orderModel->getCurrentClearanceByUserId($userId);
$orders = $orderModel->getFinalOrdersByUserId($userId);
$isAdmin = $orderModel->isUserAdmin($userId);

?>
<html>
<head>
    <title>Invoice</title>
</head>
<body>
    <h1>Invoice</h1>
    <p>Generated at: <?php echo date('Y-m-d H:i:s'); ?></p>
    <h2>Summary</h2>
    <p>Total Sold: <?php echo $clearance['total_sold']; ?></p>
    <p>Clearance Amount: <?php echo $clearance['clearance_amount']; ?></p>
    <h2>Details</h2>
    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Unit Price</th>
                <th>Quantity</th>
                <th>Sold</th>
                <th>Stock</th>
                <th>Returned</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                <td><?php echo $order['unit_price']; ?></td>
                <td><?php echo $order['quantity']; ?></td>
                <td><?php echo $order['sold']; ?></td>
                <td><?php echo $order['stock']; ?></td>
                <td><?php echo $order['returned']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($isAdmin): ?>
        <button id="confirm-invoice-btn">Confirm</button>
    <?php else: ?>
        <p>Status: Pending Confirm</p>
    <?php endif; ?>
    <form action="clearance_history.php"><button type="submit">View Clearance History</button></form>
    <script src="/assets/js/invoice-actions.js"></script>
</body>
</html>
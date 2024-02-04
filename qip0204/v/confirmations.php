<?php
session_start();

// Autoload classes
spl_autoload_register(function ($class_name) {
    require_once '../m/' . $class_name . '.php';
});

// Check if user is admin
$db = new DB();
$orderModel = new Order($db);
$userId = $_SESSION['user_id'];
if (!$orderModel->isUserAdmin($userId)) {
    die('Unauthorized access.');
}

// Fetch all unconfirmed invoices from 'clearance' table
$unconfirmedInvoices = $orderModel->getUnconfirmedInvoices();

?>
<html>
<head>
    <title>Confirmations</title>
    <style>
    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      padding: 8px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background-color: #f2f2f2;
    }

    tr:hover {
      background-color: #f5f5f5;
    }

    .confirm-btn {
      padding: 5px 10px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 3px;
      cursor: pointer;
    }

    .confirm-btn:hover {
      background-color: #45a049;
    }

    </style>
</head>
<body>
    <h1>Invoice Confirmations</h1>
    <!-- List all unconfirmed invoices here -->
    <table>
      <thead>
        <tr>
          <th>Invoice ID</th>
          <th>User ID</th>
          <th>Total Sold</th>
          <th>Clearance Amount</th>
          <th>Date Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($unconfirmedInvoices as $invoice): ?>
        <tr>
          <td><?php echo $invoice['id']; ?></td>
          <td><?php echo $invoice['user_id']; ?></td>
          <td><?php echo $invoice['total_sold']; ?></td>
          <td><?php echo $invoice['clearance_amount']; ?></td>
          <td><?php echo $invoice['created_at']; ?></td>
          <td>
            <button class="confirm-btn" onclick="confirmInvoice(<?php echo $invoice['id']; ?>)">Confirm</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <!-- Add buttons or links that allow admin to confirm each invoice -->
    <script>
    function confirmInvoice(invoiceId) {
      fetch('/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'confirm_invoice', invoiceId: invoiceId })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Invoice confirmed successfully!');
          window.location.reload(); // Reload the page to update the list
        } else {
          alert('Error confirming invoice: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while confirming the invoice.');
      });
    }
    </script>
</body>
</html>
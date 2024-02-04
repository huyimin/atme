<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Autoload classes
spl_autoload_register(function ($class_name) {
    require_once '../m/' . $class_name . '.php';
});

$db = new DB();
$orderModel = new Order($db);
$userId = $_SESSION['user_id'];

if ($orderModel->isUserAdmin($userId)) {
    $clearanceHistory = $orderModel->getAllClearanceHistories(); // Fetch all clearance histories
} else {
    $clearanceHistory = $orderModel->getClearanceHistoryByUserId($userId); // Fetch clearance history for the user
}

$users = [];
if ($orderModel->isUserAdmin($userId)) {
    $users = $orderModel->getAllUsers();
}
// Check if a specific user ID is selected and fetch clearance histories for that user
$selectedUserId = isset($_GET['user_id']) ? $_GET['user_id'] : null;
if ($selectedUserId) {
    $clearanceHistory = $orderModel->getClearanceHistoryByUserId($selectedUserId);
}

if ($orderModel->isUserAdmin($userId)): ?>
    <form action="clearance_history.php" method="get">
        <select name="user_id" onchange="this.form.submit()">
            <option value="">Select a user</option>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>" <?php echo $selectedUserId == $user['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($user['username']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
<?php endif; ?>

<html>
<head>
    <style>
    .modal {
      display: none; /* Hidden by default */
      position: fixed;
      z-index: 1; /* Sit on top */
      left: 0;
      top: 0;
      width: 100%; /* Full width */
      height: 100%; /* Full height */
      overflow: auto; /* Enable scroll if needed */
      background-color: rgb(0,0,0); /* Fallback color */
      background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
    }

    .modal-content {
      background-color: #fefefe;
      margin: 15% auto; /* 15% from the top and centered */
      padding: 20px;
      border: 1px solid #888;
      width: 80%; /* Could be more or less, depending on screen size */
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
    }

    .close:hover,
    .close:focus {
      color: black;
      text-decoration: none;
      cursor: pointer;
    }
    </style>
    <title>Clearance History</title>
</head>
<body>
    <!-- The Modal -->
    <div id="myModal" class="modal">

      <!-- Modal content -->
      <div class="modal-content">
        <span class="close">&times;</span>
        <div id="modal-body"></div>
      </div>

    </div>
    <h1>Clearance History</h1>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>User</th>
                <th>Total Sold</th>
                <th>Clearance Amount</th>
                <th>Details</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clearanceHistory as $history): ?>
            <tr>
                <td><?php echo $history['created_at']; ?></td>
                <td><?php echo $history['user_id']; ?></td>
                <td><?php echo $history['total_sold']; ?></td>
                <td><?php echo $history['clearance_amount']; ?></td>
                <td>
                    <a href="#" class="details-link" data-items='<?php echo $history['sold_items']; ?>'>Click For Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <script>
    // Get the modal
    var modal = document.getElementById('myModal');

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    // When the user clicks on the details link, open the modal
    document.querySelectorAll('.details-link').forEach(function(link) {
        link.onclick = function() {
            var soldItems = JSON.parse(this.dataset.items);
            var modalBody = document.getElementById('modal-body');
            modalBody.innerHTML = ''; // Clear existing content
            var list = document.createElement('ul');
            soldItems.forEach(function(item) {
                var listItem = document.createElement('li');
                listItem.textContent = `Item Name: ${item.item_name}, Unit Price: ${item.unit_price}, Quantity: ${item.quantity}, Sold: ${item.sold}, Stock: ${item.stock}, Returned: ${item.returned}`;
                list.appendChild(listItem);
            });
            modalBody.appendChild(list);
            modal.style.display = "block";
        }
    });
    </script>
</body>
</html>
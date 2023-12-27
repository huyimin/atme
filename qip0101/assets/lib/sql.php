<?php
session_start(); 
include 'CRUD.php';

$crud = new CRUD();

// Handle the post request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create'])) {
        // Create new item
        $crud->create('items', [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'img' => $_POST['img'],
            'specs' => $_POST['specs']
        ]);
    } elseif (isset($_POST['update'])) {
        // Update item
        $crud->update('items', [
            'name' => $_POST['name'],
            'description' => $_POST['description'],
            'price' => $_POST['price'],
            'img' => $_POST['img'],
            'specs' => $_POST['specs']
        ], $_POST['item_id']);
    } elseif (isset($_POST['delete'])) {
        // Delete item
        $crud->delete('items', $_POST['item_id']);
    }

    // Check if an item_id is posted for update/delete selection
    if (isset($_POST['item_id']) && $_POST['item_id'] != '') {
        $_SESSION['selected_item_id'] = $_POST['item_id']; // Store the selected item id in the session
    }

    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch all items to display
$items = $crud->read('items');

// Check if we have a selected item stored in the session
if (isset($_SESSION['selected_item_id'])) {
    $selectedItem = $crud->readOne('items', $_SESSION['selected_item_id']);
    unset($_SESSION['selected_item_id']); // Clear the selected item id from the session
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Item Manager</title>
</head>
<body>

<h2>Item Manager</h2>

<!-- Create Form -->
<h3>Create Item</h3>
<form method="post">
    Name: <input type="text" name="name" required><br>
    Description: <input type="text" name="description" required><br>
    Price: <input type="number" name="price" required><br>
    Image URL: <input type="text" name="img" required><br>
    Specs: <input type="text" name="specs" required><br>
    <input type="submit" name="create" value="Create Item">
</form>

<!-- Update and Delete Dropdown -->
<h3>Update/Delete Item</h3>
<form method="post" id="updateDeleteForm">
    <select name="item_id" onchange="document.getElementById('updateDeleteForm').submit();">
        <option value="">Select an Item</option>
        <?php foreach ($items as $item) { ?>
            <option value="<?php echo htmlspecialchars($item['id']); ?>" <?php if (isset($selectedItem) && $selectedItem['id'] == $item['id']) echo 'selected'; ?>>
                <?php echo htmlspecialchars($item['name']); ?>
            </option>
        <?php } ?>
    </select>
</form>

<?php
// Only display the update/delete form if an item is selected
if (isset($selectedItem)) {
?>

<!-- Update and Delete Forms for the selected item -->
<form method="post">
    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($selectedItem['id']); ?>">
    Name: <input type="text" name="name" value="<?php echo htmlspecialchars($selectedItem['name']); ?>" required><br>
    Description: <input type="text" name="description" value="<?php echo htmlspecialchars($selectedItem['description']); ?>" required><br>
    Price: <input type="number" name="price" value="<?php echo htmlspecialchars($selectedItem['price']); ?>" required><br>
    Image URL: <input type="text" name="img" value="<?php echo htmlspecialchars($selectedItem['img']); ?>" required><br>
    Specs: <input type="text" name="specs" value="<?php echo htmlspecialchars($selectedItem['specs']); ?>" required><br>
    <input type="submit" name="update" value="Update Item">
    <input type="submit" name="delete" value="Delete Item">
</form>

<?php } ?>

</body>
</html>
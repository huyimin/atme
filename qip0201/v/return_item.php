<?php
$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['itemId'];
$quantityToReturn = $data['quantityToReturn'];

$orderController = new OrderController(new Order($db));
$result = $orderController->return($userId, $itemId, $quantityToReturn);

header('Content-Type: application/json');
echo json_encode($result);
<?php
$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
$itemId = $data['itemId'];
$quantityToSell = $data['quantityToSell'];

$orderController = new OrderController(new Order($db));
$result = $orderController->sell($userId, $itemId, $quantityToSell);

header('Content-Type: application/json');
echo json_encode($result);
<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!isset($_SESSION['user_id']) && $_SERVER['REQUEST_URI'] != '/login.php') {
    header('Location: /login.php');
    exit;
}

spl_autoload_register(function ($class_name) {
    if(file_exists('c/' . $class_name . '.php')){
        require_once 'c/' . $class_name . '.php';
    } elseif(file_exists('m/' . $class_name . '.php')){
        require_once 'm/' . $class_name . '.php';
    }
});

function loadView($view, $data = null)
{
    if(is_array($data)){
        extract($data);
    }
    require 'v/' . $view . '.php';
}

$db = new DB();
$userModel = new User($db);
$userController = new UserController($userModel);

if(isset($_SESSION['user_id'])) {
    $user = $userController->getUser($_SESSION['user_id']);
    if(!$user) {
        unset($_SESSION['user_id']);
        header('Location: /login.php');
        exit;
    }
}
  
$itemModel = new Item($db);
$itemController = new ItemController($itemModel);
$items = $itemController->index();

$cartModel = new Cart($db);
$cartController = new CartController($cartModel);

$orderController = new OrderController(new Order($db));
$orders = $orderController->getOrders();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'load_orders') {
    header('Content-Type: text/html');
    $orders = $orderController->getOrders();
    loadView('order', ['orders' => $orders]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['action'])) {
        switch ($data['action']) {
            case 'update_order':
                $cartController->updateItemQuantity();
                break;
            case 'delete_item':
                $cartController->removeItem();
                break;
            case 'confirm_order':
                $cartController->confirmOrder(); 
                break;
            case 'sell':
                $result = $orderController->sell($_SESSION['user_id'], $data['itemId'], $data['quantityToSell']);
                echo json_encode($result); // This line is required to send the response back to JS
                break;
            case 'return':
                $result = $orderController->return($_SESSION['user_id'], $data['itemId'], $data['quantityToReturn']);
                echo json_encode($result); // This line is required to send the response back to JS
                break;
            case 'finalize_deal':
                $result = $orderController->finalizeDeal($data['itemIds']);
                echo json_encode($result);
                break;   
            default:
                // Handle other POST actions or return an error
                echo json_encode(['success' => false, 'message' => 'Action not recognized']);
                break;
        }
    } else {
        // Handle the checkout action or other POST actions without an 'action' parameter
        $cartController->checkout();
    }
    exit;
}

// Check for user requests to view their orders
if (isset($_GET['view']) && $_GET['view'] === 'my_orders') {
    $userId = $_SESSION['user_id']; // Ensure this is the logged-in user's ID
    $orders = $orderController->getFinalOrdersByUserId($userId);
    loadView('user_orders', ['orders' => $orders]);
    exit;
}

// load main view
loadView('main', [
    'items' => $items,
    'orders' => $orders,
]);
?>
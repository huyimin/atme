<?php
session_start();
spl_autoload_register(function ($class_name) {
    if(file_exists('c/' . $class_name . '.php')){
        require_once 'c/' . $class_name . '.php';
    } elseif(file_exists('m/' . $class_name . '.php')){
        require_once 'm/' . $class_name . '.php';
    }
});

$db = new DB();
$userModel = new User($db);
$userController = new UserController($userModel);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = $userController->login($username, $password);

    if ($user) {
        // The user is authenticated successfully.
        $_SESSION['user_id'] = $user['id'];
        header('Location: /index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}

?>

<!DOCTYPE html>
<html>
<body>

<h2>Login</h2>

<form method="post">
  <?php if (isset($error)) { ?>
    <p style="color: red;"><?php echo $error; ?></p>
  <?php } ?>
  <div class="container">
    <label for="username"><b>Username</b></label>
    <input type="text" placeholder="Enter Username" name="username" required>

    <label for="password"><b>Password</b></label>
    <input type="password" placeholder="Enter Password" name="password" required>

    <button type="submit">Login</button>
  </div>
</form>

</body>
</html>
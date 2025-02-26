<?php
require('autoload.php');

$is_logout = isset($_GET['logout']) ? $_GET['logout'] : false;

if($is_logout == true){
    unset($_SESSION['auth']);

    header('location: ../login.php');
}

$username = isset($_POST['username']) ? $_POST['username'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;

if($username == "admin" && $password == "stsjho"){
    $_SESSION['auth'] = [
        'id'    => 999999999,
        'nama'  => 'Admin HO'
    ];

    header('location: ../index.php');

    exit();
}

$_SESSION['failed_auth'] = "Username atau password salah";

header('location: ../login.php');
exit();
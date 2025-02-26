<?php
session_start();

date_default_timezone_set('Asia/Jakarta');

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$db     = 'yamahast_data';

$conn = mysqli_connect($host, $user, $pass, $db);

if (mysqli_connect_errno()) {
  echo 'Failed to connect to MySQL: ' . mysqli_connect_error();
  exit();
}
?>
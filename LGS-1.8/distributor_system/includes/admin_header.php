<?php
@session_start();
require_once __DIR__ . '/../functions.php';

// Ensure username + initial are always available
$username = $_SESSION['user']['username'] ?? 'Admin';
$initial  = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
 <link rel="shortcut icon" href="../assets/images/logo/favicon.jpg" type="image/x-icon" />
  <title><?php echo isset($page_title) ? $page_title : 'Admin Dashboard | Lead System'; ?></title>

  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/lineicons.css" />
  <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css" />
  <link rel="stylesheet" href="../assets/css/fullcalendar.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/admin_index.css" />
</head>
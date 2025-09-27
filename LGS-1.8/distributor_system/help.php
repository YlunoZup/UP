<?php
include __DIR__ . '/includes/help_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact Admin</title>
  <link rel="shortcut icon" href="assets/images/logo/favicon.jpg" type="image/x-icon" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: Arial, sans-serif;
    }

    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background: url('assets/images/logo/bg.jpg') no-repeat center center fixed;
      background-size: cover;
      position: relative;
    }

    /* Translucent overlay */
    body::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.5); /* Light translucent layer */
      z-index: 0;
    }

    .card {
      position: relative;
      background: rgba(255, 255, 255, 0.9);
      padding: 20px 30px;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      max-width: 320px;
      width: 90%;
      text-align: center;
      z-index: 1;
    }

    .card img {
      width: 80px;
      height: 80px;
      object-fit: contain;
      margin-bottom: 15px;
      display: block;
      margin-left: auto;
      margin-right: auto;
    }

    .card h2 {
      font-size: 1.2rem;
      color: #333;
      margin-bottom: 10px;
    }

    .card p {
      font-size: 0.95rem;
      color: #555;
    }
  </style>
</head>
<body>
  <div class="card">
    <img src="assets/images/logo/favicon.jpg" alt="Logo">
    <h2>Contact TL for more!</h2>
    <p>- Admin</p>
  </div>
</body>
</html>

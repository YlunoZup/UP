<?php
require_once __DIR__ . '/../functions.php';
require_role('admin');

@session_start();
$csrf = generate_csrf_token();

$username = $_SESSION['user']['username'] ?? 'Admin';
$initial = strtoupper(substr($username, 0, 1));

$err = $msg = '';
if (!empty($_SESSION['flash_err'])) { 
    $err = $_SESSION['flash_err']; 
    unset($_SESSION['flash_err']); 
}
if (!empty($_SESSION['flash_msg'])) { 
    $msg = $_SESSION['flash_msg']; 
    unset($_SESSION['flash_msg']); 
}

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Page-specific CSS -->
<link rel="stylesheet" href="../assets/css/admin_leads.css">

<body>
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="overlay"></div>

<main class="main-wrapper">
  <?php require_once __DIR__ . '/../includes/admin_navbar.php'; ?>

  <section class="container-fluid py-4">
    <div class="row g-4">
      <div class="col-12">
        <div class="card card-style p-3">

          <?php if ($err): ?>
            <div class="alert alert-danger"><?= esc($err) ?></div>
          <?php endif; ?>
          <?php if ($msg): ?>
            <div class="alert alert-success"><?= esc($msg) ?></div>
          <?php endif; ?>

          <?php require __DIR__ . '/leads_modals.php'; ?>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
</body>
</html>
<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
require_once __DIR__ . '/../functions.php';

$me = current_user();
$username = $me['username'] ?? 'Admin';
$initial = strtoupper(substr($username, 0, 1));
$profile_pic = '';

// Fetch profile picture if exists
if (isset($me['id'])) {
  $db = db_connect();
  $stmt = $db->prepare("SELECT file_path FROM profile_pics WHERE user_id = ?");
  $stmt->bind_param('i', $me['id']);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_assoc()) {
    $profile_pic = '../' . $row['file_path'];
  }
  $stmt->close();
  $db->close();
}
?>

<header class="header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div class="header-left d-flex align-items-center">
      <!-- Menu toggle -->
      <div class="menu-toggle-btn mr-15">
        <button id="menu-toggle" class="main-btn primary-btn btn-hover">
          <i class="lni lni-chevron-left me-2"></i> Menu
        </button>
      </div>

      <!-- Search -->
      <div class="header-search d-none d-md-flex">
        <form action="#">
          <input type="text" placeholder="Search..." />
          <button><i class="lni lni-search-alt"></i></button>
        </form>
      </div>
    </div>

    <!-- User profile -->
    <div class="user-profile">
      <?php if ($profile_pic && file_exists($profile_pic)): ?>
        <img src="<?= esc($profile_pic) ?>" alt="Profile" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
      <?php else: ?>
        <div class="profile-circle"><?= esc($initial) ?></div>
      <?php endif; ?>
      <span><?= esc($username) ?></span>
    </div>
  </div>
</header>
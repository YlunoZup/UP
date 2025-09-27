<?php
// agent_navbar.php
$username = $me['username'] ?? 'Agent';
$initial = strtoupper(substr($username, 0, 1));
?>
<header class="header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div class="header-left d-flex align-items-center">
      <div class="menu-toggle-btn mr-15">
        <button id="menu-toggle" class="main-btn primary-btn btn-hover">
          <i class="lni lni-chevron-left me-2"></i> Menu
        </button>
      </div>
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
<?php
// agent_sidebar.php
// detect current page filename
$currentPage = basename($_SERVER['PHP_SELF']); 
?>
<aside class="sidebar-nav-wrapper active">
  <div class="navbar-logo">
    <a href="index.php">
      <img src="../assets/images/logo/logo.png" alt="logo" />
    </a>
  </div>
  <nav class="sidebar-nav">
    <ul>
      <!-- Dashboard group -->
      <li class="nav-item nav-item-has-children <?php if(in_array($currentPage, ['index.php','table.php'])) echo 'active'; ?>">
        <a href="#0" class="collapsed" data-bs-toggle="collapse" data-bs-target="#ddmenu_dashboard">
          <span class="text">Dashboard</span>
        </a>
        <ul id="ddmenu_dashboard" class="collapse show dropdown-nav">
          <li><a href="index.php" class="<?php if($currentPage == 'index.php') echo 'active'; ?>">Main</a></li>
          <li><a href="table.php" class="<?php if($currentPage == 'table.php') echo 'active'; ?>">Leads Table</a></li>
        </ul>
      </li>

      <span class="divider"><hr /></span>

      <!-- Notifications -->
      <li class="nav-item">
        <a href="notification.php" class="<?php if($currentPage == 'notification.php') echo 'active'; ?>">
          <span class="text">Notifications</span>
        </a>
      </li>

      <!-- Profile -->
      <li class="nav-item">
        <a href="profile.php" class="<?php if($currentPage == 'profile.php') echo 'active'; ?>">
          <span class="text">Profile</span>
        </a>
      </li>
    </ul>
  </nav>

  <a href="../logout.php" class="logout-btn">Logout</a>
</aside>
<div class="overlay active"></div>
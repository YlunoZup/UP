<?php
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
      <li class="nav-item nav-item-has-children <?php if(in_array($currentPage, ['index.php','leads.php','upload.php'])) echo 'active'; ?>">
        <a href="#0" class="collapsed" data-bs-toggle="collapse" data-bs-target="#ddmenu_dashboard">
          <span class="text">Dashboard</span>
        </a>
        <ul id="ddmenu_dashboard" class="collapse show dropdown-nav">
          <li><a href="index.php" class="<?php if($currentPage == 'index.php') echo 'active'; ?>">Agents</a></li>
          <li><a href="leads.php" class="<?php if($currentPage == 'leads.php') echo 'active'; ?>">Leads</a></li>
          <li><a href="upload.php" class="<?php if($currentPage == 'upload.php') echo 'active'; ?>">Upload</a></li>
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
<?php
require_once __DIR__ . '/../functions.php';
require_role('admin');

@session_start();

$db = db_connect();
$me = current_user();
$user_id = (int)$me['id'];
$err = $msg = '';

include __DIR__ . '/../includes/agent_header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $_SESSION['flash_err'] = 'Invalid CSRF token.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $updates = [];
        $params = [];
        $types = '';

        if ($username === '' || $full_name === '') {
            $_SESSION['flash_err'] = 'Username and full name are required.';
        } else {
            // Check if username changed and is taken
            if ($username !== $me['username']) {
                $check_stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $check_stmt->bind_param('si', $username, $user_id);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();
                if ($check_res->num_rows > 0) {
                    $_SESSION['flash_err'] = 'Username already taken.';
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    $updates[] = 'username = ?';
                    $params[] = $username;
                    $types .= 's';
                }
            }

            // Always update full_name
            $updates[] = 'full_name = ?';
            $params[] = $full_name;
            $types .= 's';

            // Update password if provided
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $updates[] = 'password = ?';
                $params[] = $hash;
                $types .= 's';
            }

            if (!empty($updates)) {
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $types .= 'i';
                $params[] = $user_id;
                $stmt = $db->prepare($sql);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $_SESSION['flash_msg'] = 'Profile updated successfully.';
                        // Refresh session user data
                        $refresh_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
                        $refresh_stmt->bind_param('i', $user_id);
                        $refresh_stmt->execute();
                        $refresh_res = $refresh_stmt->get_result();
                        $_SESSION['user'] = $refresh_res->fetch_assoc();
                        $refresh_stmt->close();
                        $me = $_SESSION['user'];
                    } else {
                        $_SESSION['flash_err'] = 'No changes made.';
                    }
                } else {
                    $_SESSION['flash_err'] = 'Update failed: ' . $db->error;
                }
                $stmt->close();
            }
        }

        // Handle profile pic upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['flash_err'] = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP.';
            } elseif ($file['size'] > $max_size) {
                $_SESSION['flash_err'] = 'File too large. Max 2MB.';
            } else {
                $upload_dir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Delete old pic if exists
                    $old_stmt = $db->prepare("SELECT file_path FROM profile_pics WHERE user_id = ?");
                    $old_stmt->bind_param('i', $user_id);
                    $old_stmt->execute();
                    $old_res = $old_stmt->get_result();
                    if ($old_row = $old_res->fetch_assoc()) {
                        $old_full_path = __DIR__ . '/../' . $old_row['file_path'];
                        if (file_exists($old_full_path)) {
                            unlink($old_full_path);
                        }
                    }
                    $old_stmt->close();

                    // Insert new
                    $ins_stmt = $db->prepare("REPLACE INTO profile_pics (user_id, file_path) VALUES (?, ?)");
                    $db_path = 'uploads/profiles/' . $file_name;
                    $ins_stmt->bind_param('is', $user_id, $db_path);
                    if ($ins_stmt->execute()) {
                        $_SESSION['flash_msg'] = $_SESSION['flash_msg'] ?? 'Profile picture updated.';
                    } else {
                        $_SESSION['flash_err'] = 'Pic upload failed: ' . $db->error;
                        unlink($file_path); // Cleanup
                    }
                    $ins_stmt->close();
                } else {
                    $_SESSION['flash_err'] = 'Failed to upload file.';
                }
            }
        }
    }
}

// Flash messages
if (!empty($_SESSION['flash_err'])) { $err = $_SESSION['flash_err']; unset($_SESSION['flash_err']); }
if (!empty($_SESSION['flash_msg'])) { $msg = $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); }

// Fetch current profile pic
$profile_pic = '';
$stmt = $db->prepare("SELECT file_path FROM profile_pics WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $profile_pic = '../' . $row['file_path'];
}
$stmt->close();

$db->close();
$csrf = generate_csrf_token();

// detect current page for sidebar active state
$currentPage = basename($_SERVER['PHP_SELF']); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Profile | Lead System</title>

  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/lineicons.css" />
  <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/admin_index.css" />
  <link rel="stylesheet" href="../assets/css/agent_profile.css" />
</head>
<body>
  <!-- Sidebar -->
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

  <div class="overlay active"></div>

  <!-- Main content -->
  <main class="main-wrapper">
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
        <div class="user-profile">
          <?php if ($profile_pic && file_exists($profile_pic)): ?>
            <img src="<?= esc($profile_pic) ?>" alt="Profile" class="rounded-circle" style="width: 36px; height: 36px; object-fit: cover;">
          <?php else: ?>
            <div class="profile-circle"><?= esc(strtoupper(substr($me['username'] ?? 'A', 0, 1))) ?></div>
          <?php endif; ?>
          <span><?= esc($me['username'] ?? 'Admin') ?></span>
        </div>
      </div>
    </header> 

    <section class="py-4">
      <div class="container-fluid">
        <div class="row justify-content-center">
          <div class="col-12 col-md-8 col-lg-6">
            <div class="card card-style p-4">
              <h4 class="mb-4 text-center">Edit Profile</h4>

              <?php if ($err): ?>
                <div class="alert alert-danger"><?= esc($err) ?></div>
              <?php endif; ?>
              <?php if ($msg): ?>
                <div class="alert alert-success"><?= esc($msg) ?></div>
              <?php endif; ?>

              <form method="post" enctype="multipart/form-data" class="row g-3" id="profile-form">
                <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">

                <div class="col-12">
                  <label class="form-label">Profile Picture</label>
                  <div class="text-center mb-3">
                    <?php if ($profile_pic && file_exists($profile_pic)): ?>
                      <img src="<?= esc($profile_pic) ?>" alt="Profile Pic" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                    <?php else: ?>
                      <div class="profile-circle d-inline-block"><?= esc(strtoupper(substr($me['username'] ?? 'A', 0, 1))) ?></div>
                    <?php endif; ?>
                  </div>
                  <input type="file" name="profile_pic" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                  <div class="form-text">Max 2MB. JPG, PNG, GIF, WebP only.</div>
                </div>

                <div class="col-12">
                  <label class="form-label">Username</label>
                  <input type="text" name="username" class="form-control" value="<?= esc($me['username'] ?? '') ?>" required>
                  <div class="form-text">Must be unique.</div>
                </div>

                <div class="col-12">
                  <label class="form-label">Full Name</label>
                  <input type="text" name="full_name" class="form-control" value="<?= esc($me['full_name'] ?? '') ?>" required>
                </div>

                <div class="col-12">
                  <label class="form-label">New Password (leave blank to keep current)</label>
                  <input type="password" name="password" class="form-control" id="password-input">
                  <div class="form-text">Minimum 3 characters.</div>
                </div>

                <div class="col-12">
                  <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script>
    // Sidebar toggle
    const menuToggle = document.getElementById("menu-toggle");
    const sidebar = document.querySelector("aside.sidebar-nav-wrapper");
    const overlay = document.querySelector(".overlay");

    function shouldShowOverlay() {
      if (!sidebar || !overlay) return false;
      return sidebar.classList.contains("active") && window.innerWidth < 992;
    }

    function updateOverlayState() {
      if (!overlay || !sidebar) return;
      if (shouldShowOverlay()) {
        overlay.classList.add("active");
      } else {
        overlay.classList.remove("active");
      }
    }

    if (sidebar && overlay) {
      updateOverlayState();
      document.addEventListener("DOMContentLoaded", updateOverlayState);
      let resizeTimer;
      window.addEventListener("resize", () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updateOverlayState, 150);
      });
      overlay.addEventListener("click", () => {
        sidebar.classList.remove("active");
        overlay.classList.remove("active");
      });
    }

    if (menuToggle && sidebar && overlay) {
      menuToggle.addEventListener("click", () => {
        sidebar.classList.toggle("active");
        updateOverlayState();
      });
    }

    // Password confirm logic
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('profile-form');
      const passwordInput = document.getElementById('password-input');

      if (!form) return;

      form.addEventListener('submit', function (e) {
        const pwd = passwordInput.value.trim();

        if (pwd === '') return;

        if (pwd.length < 3) {
          e.preventDefault();
          alert('Password must be at least 3 characters long.');
          passwordInput.focus();
          return;
        }

        const confirmMsg = 'You are about to change this account\'s password. This will update login credentials. Are you sure you want to continue?';
        if (!confirm(confirmMsg)) {
          e.preventDefault();
          passwordInput.focus();
          return;
        }
      });
    });
  </script>
</body>
</html>

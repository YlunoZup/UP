<?php
require_once __DIR__ . '/../functions.php';
require_role('admin');

@session_start();

$db = db_connect();
$err = $msg = '';

// flash messages
if (!empty($_SESSION['flash_err'])) {
    $err = $_SESSION['flash_err'];
    unset($_SESSION['flash_err']);
}
if (!empty($_SESSION['flash_msg'])) {
    $msg = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $_SESSION['flash_err'] = 'Invalid CSRF token.';
    } else {
        if (isset($_POST['delete_id'])) {
            $delete_id = (int)$_POST['delete_id'];
            if ($delete_id > 0) {
                $del = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'agent'");
                $del->bind_param('i', $delete_id);
                if ($del->execute() && $del->affected_rows > 0) {
                    $_SESSION['flash_msg'] = "Agent deleted.";
                } else {
                    $_SESSION['flash_err'] = "Failed to delete agent.";
                }
                $del->close();
            } else {
                $_SESSION['flash_err'] = "Invalid agent ID.";
            }
        } else {
            // create agent
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $full = trim($_POST['full_name'] ?? '');
            if ($username === '' || $password === '' || $full === '') {
                $_SESSION['flash_err'] = 'All fields required.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, 'agent')");
                $ins->bind_param('sss', $username, $hash, $full);
                if ($ins->execute()) {
                    $_SESSION['flash_msg'] = 'Agent created.';
                } else {
                    $_SESSION['flash_err'] = 'Error: ' . $db->error;
                }
                $ins->close();
            }
        }
    }
    header("Location: index.php");
    exit;
}

// fetch agents
$res = $db->query("SELECT id, username, full_name, role, created_at FROM users WHERE role='agent' ORDER BY id DESC");
$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;
$db->close();

$csrf = generate_csrf_token();
$username = $_SESSION['user']['username'] ?? 'Admin';
$initial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon" />
  <title>Admin Dashboard | Lead System</title>

  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/lineicons.css" />
  <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css" />
  <link rel="stylesheet" href="../assets/css/fullcalendar.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />

  <style>
    aside.sidebar-nav-wrapper {
      width: 250px;
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background: #fff;
      z-index: 1000;
      transform: translateX(-250px);
      transition: transform 0.3s ease;
      display: flex;
      flex-direction: column;
    }
    aside.sidebar-nav-wrapper.active { transform: translateX(0); }
    main.main-wrapper { margin-left: 0; transition: margin-left 0.3s ease; }
    aside.sidebar-nav-wrapper.active ~ main.main-wrapper { margin-left: 250px; }
    .overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.3);
      z-index: 999;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s ease;
    }
    .overlay.active { opacity: 1; pointer-events: auto; }
    .logout-btn {
      display: block;
      width: 100%;
      padding: 10px 15px;
      text-align: center;
      border: none;
      color: #dc3545;
      font-size: 14px;
      font-weight: bold;
      text-decoration: none;
      margin-top: auto;
    }
    .logout-btn:hover { color: #a71d2a; }

    /* Header */
    header.header {
      width: 100%;
      background: #fff;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    header .header-left {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-right: 50px; /* space from right edge */
    }
    .profile-circle {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: #007bff;
      color: #fff;
      display: flex;
      justify-content: center;
      align-items: center;
      font-weight: bold;
      font-size: 16px;
    }
    .user-profile span {
      font-size: 14px;
      font-weight: 500;
      color: #333;
    }



  </style>
</head>
<body>
  <!-- Sidebar (already open by default with "active") -->
  <aside class="sidebar-nav-wrapper active">
    <div class="navbar-logo">
      <a href="index.php"><img src="../assets/images/logo/logo.png" alt="logo" /></a>
    </div>
    <nav class="sidebar-nav">
      <ul>
        <li class="nav-item nav-item-has-children active">
          <a href="#0" class="collapsed" data-bs-toggle="collapse" data-bs-target="#ddmenu_dashboard">
            <span class="text">Dashboard</span>
          </a>
          <ul id="ddmenu_dashboard" class="collapse show dropdown-nav">
            <li><a href="index.php" class="active">Agents</a></li>
            <li><a href="leads.php">Leads</a></li>
            <li><a href="upload.php">Upload</a></li>
          </ul>
        </li>
        <span class="divider"><hr /></span>
        <li class="nav-item">
          <a href="notification.php"><span class="text">Notifications</span></a>
        </li>
      </ul>
    </nav>
    <a href="../logout.php" class="logout-btn">Logout</a>
  </aside>

  <!-- Overlay also active so it matches -->
  <div class="overlay active"></div>

  <!-- Main -->
  <main class="main-wrapper">
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

        <!-- User profile on far right -->
        <div class="user-profile">
          <div class="profile-circle"><?= esc($initial) ?></div>
          <span><?= esc($username) ?></span>
        </div>
      </div>
    </header>


    <section class="table-components">
      <div class="container-fluid">
        <div class="row mt-5">
          <div class="col-lg-12">
            <div class="card-style mb-30">
              <div class="card mb-3">
                <div class="card-body">
                  <h4 class="mb-3">Agents Management</h4>

                  <?php if ($err): ?><div class="alert alert-danger"><?= esc($err) ?></div><?php endif; ?>
                  <?php if ($msg): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>

                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>ID</th><th>Username</th><th>Full name</th><th>Role</th><th>Created</th><th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($users as $u): ?>
                        <tr>
                          <td><?= esc((string)$u['id']) ?></td>
                          <td><?= esc($u['username']) ?></td>
                          <td><?= esc($u['full_name']) ?></td>
                          <td><?= esc($u['role']) ?></td>
                          <td><?= esc($u['created_at']) ?></td>
                          <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete this agent? This will also remove all their leads and uploads.');">
                              <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                              <input type="hidden" name="delete_id" value="<?= esc((string)$u['id']) ?>">
                              <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>

                  <hr>
                  <h5>Create Agent</h5>
                  <form method="post" class="row g-3">
                    <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                    <div class="col-md-4">
                      <label class="form-label">Username</label>
                      <input name="username" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Password</label>
                      <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Full name</label>
                      <input name="full_name" class="form-control" required>
                    </div>
                    <div class="col-12">
                      <button class="btn btn-success">Create Agent</button>
                    </div>
                  </form>

                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script>
    const menuToggle = document.getElementById("menu-toggle");
    const sidebar = document.querySelector("aside.sidebar-nav-wrapper");
    const overlay = document.querySelector(".overlay");

    menuToggle.addEventListener("click", () => {
      sidebar.classList.toggle("active");
      overlay.classList.toggle("active");
    });
    overlay.addEventListener("click", () => {
      sidebar.classList.remove("active");
      overlay.classList.remove("active");
    });
  </script>
</body>
</html>

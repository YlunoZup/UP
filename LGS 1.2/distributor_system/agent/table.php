<?php
require_once __DIR__ . '/../functions.php';
require_role('agent');

$db = db_connect();
$me = current_user();
$agent_id = (int)$me['id'];
$selected_date = $_GET['date'] ?? date('Y-m-d');
$mode = $_GET['mode'] ?? 'paged';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;

// NEW: Lead status filter
$status_filter = $_GET['status_filter'] ?? 'all';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_lead'])) {
    verify_csrf_token($_POST['csrf'] ?? '');
    $lead_id = (int)($_POST['lead_id']);
    $updates = [];
    $params = [];
    $types = '';

    if (isset($_POST['status'])) { $updates[] = 'status = ?'; $params[] = $_POST['status']; $types .= 's'; }
    if (isset($_POST['notes'])) { $updates[] = 'notes = ?'; $params[] = trim($_POST['notes']); $types .= 's'; }

    if (!empty($updates)) {
        $sql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = ? AND agent_id = ?";
        $stmt = $db->prepare($sql);
        $types .= 'ii';
        $params[] = $lead_id;
        $params[] = $agent_id;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: table.php?date={$selected_date}&mode={$mode}&page={$page}&status_filter={$status_filter}&updated=1");
    exit;
}

$leads = [];
$total_rows = 0;

// Build the WHERE clause for filtering
$where_clause = "agent_id = ? AND lead_date = ?";
$params = [$agent_id, $selected_date];
$types = 'is';

if ($status_filter !== 'all') {
    $where_clause .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($mode === 'paged') {
    // Count total rows
    $sql_count = "SELECT COUNT(*) FROM leads WHERE $where_clause";
    $stmt = $db->prepare($sql_count);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->bind_result($total_rows);
    $stmt->fetch();
    $stmt->close();

    // Fetch paged data
    $offset = ($page - 1) * $per_page;
    $sql_data = "SELECT id, number, company_name, description, status, notes, lead_date, updated_at 
                 FROM leads WHERE $where_clause ORDER BY id ASC LIMIT ? OFFSET ?";
    $types_data = $types . 'ii';
    $params_data = [...$params, $per_page, $offset];
    $stmt = $db->prepare($sql_data);
    $stmt->bind_param($types_data, ...$params_data);
} else {
    // Fetch all
    $sql_data = "SELECT id, number, company_name, description, status, notes, lead_date, updated_at 
                 FROM leads WHERE $where_clause ORDER BY id ASC";
    $stmt = $db->prepare($sql_data);
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $leads[] = $r;
$stmt->close();
$db->close();
$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon" />
  <title>Dashboard | Lead System</title>

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
  <!-- Sidebar nav -->
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
            <li><a href="index.php"> Main </a></li>
            <li><a href="table.php" class="active"> Leads Table </a></li>
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

  <!-- Overlay now active by default -->
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

        <!-- User profile on far right -->
        <div class="user-profile">
          <div class="profile-circle"><?= esc(strtoupper(substr($_SESSION['user']['username'] ?? 'A', 0, 1))) ?></div>
          <span><?= esc($_SESSION['user']['username'] ?? 'Admin') ?></span>
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
                  <h4 class="mb-3">Your Leads — <?= esc($selected_date) ?></h4>

                  <!-- Date Filter -->
                  <form class="row g-3 align-items-end mb-4" method="get">
                    <input type="hidden" name="mode" value="<?= esc($mode) ?>">
                    <div class="col-md-4">
                      <label class="form-label fw-bold">Date</label>
                      <div class="input-group">
                        <a class="btn btn-outline-secondary"
                           href="?date=<?= esc(date('Y-m-d', strtotime($selected_date . ' -1 day'))) ?>&mode=<?= esc($mode) ?>&status_filter=<?= esc($status_filter) ?>">← Prev</a>
                        <input type="date" name="date" value="<?= esc($selected_date) ?>" class="form-control" onchange="this.form.submit()">
                        <a class="btn btn-outline-secondary" href="?date=<?= esc(date('Y-m-d')) ?>&mode=<?= esc($mode) ?>&status_filter=<?= esc($status_filter) ?>">Today</a>
                        <a class="btn btn-outline-secondary"
                           href="?date=<?= esc(date('Y-m-d', strtotime($selected_date . ' +1 day'))) ?>&mode=<?= esc($mode) ?>&status_filter=<?= esc($status_filter) ?>">Next →</a>
                      </div>
                    </div>
                  </form>

                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Leads for <span class="text-primary"><?= esc($selected_date) ?></span></h5>

                    <div class="d-flex gap-2">
                      <!-- Dropdown Filter -->
                      <div class="dropdown">
                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" id="statusFilterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                          Filter: <?= esc(ucfirst($status_filter)) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="statusFilterDropdown">
                          <li><a class="dropdown-item" href="?date=<?= esc($selected_date) ?>&mode=<?= esc($mode) ?>&status_filter=all">All</a></li>
                          <li><a class="dropdown-item" href="?date=<?= esc($selected_date) ?>&mode=<?= esc($mode) ?>&status_filter=Good">Good</a></li>
                          <li><a class="dropdown-item" href="?date=<?= esc($selected_date) ?>&mode=<?= esc($mode) ?>&status_filter=Bad">Bad</a></li>
                          <li><a class="dropdown-item" href="?date=<?= esc($selected_date) ?>&mode=<?= esc($mode) ?>&status_filter=N/A">N/A</a></li>
                        </ul>
                      </div>

                      <!-- Show All Leads Button -->
                      <a href="?date=<?= esc($selected_date) ?>&mode=<?= $mode === 'all' ? 'paged' : 'all' ?>&status_filter=<?= esc($status_filter) ?>" class="btn btn-sm btn-outline-dark">
                        <?= $mode === 'all' ? 'Switch to Paginated' : 'Show All Leads' ?>
                      </a>
                    </div>
                  </div>

                  <?php if (empty($leads)): ?>
                    <div class="alert alert-info">No leads for this date.</div>
                  <?php else: ?>
                    <?php $modals = ''; ?>
                    <div class="table-wrapper table-responsive">
                      <table class="table table-hover table-sm align-middle">
                        <thead class="table-light">
                          <tr>
                            <th>#</th>
                            <th>Company</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Updated</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($leads as $l): ?>
                            <?php
                              $row_class = '';
                              if ($l['status'] === 'Good') $row_class = 'table-success';
                              elseif ($l['status'] === 'Bad') $row_class = 'table-danger';
                              $modal_id = "editLeadModal" . (int)$l['id'];
                              ob_start(); ?>
                              <div class="modal fade" id="<?= esc($modal_id) ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                  <div class="modal-content">
                                    <form method="post">
                                      <div class="modal-header">
                                        <h5 class="modal-title">Edit Lead #<?= esc((string)$l['number']) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                      </div>
                                      <div class="modal-body">
                                        <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                                        <input type="hidden" name="lead_id" value="<?= esc((string)$l['id']) ?>">
                                        <div class="mb-3">
                                          <label class="form-label">Company</label>
                                          <input type="text" class="form-control" value="<?= esc($l['company_name']) ?>" disabled>
                                        </div>
                                        <div class="mb-3">
                                          <label class="form-label">Description</label>
                                          <textarea class="form-control" rows="3" disabled><?= esc($l['description']) ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                          <label class="form-label">Status</label>
                                          <select name="status" class="form-select">
                                            <?php foreach (['Good','Bad','N/A'] as $opt): ?>
                                              <option value="<?= $opt ?>" <?= $l['status']===$opt?'selected':'' ?>><?= $opt ?></option>
                                            <?php endforeach; ?>
                                          </select>
                                        </div>
                                        <div class="mb-3">
                                          <label class="form-label">Notes</label>
                                          <textarea name="notes" class="form-control" rows="3"><?= esc($l['notes']) ?></textarea>
                                        </div>
                                      </div>
                                      <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="update_lead" class="btn btn-primary">Save Changes</button>
                                      </div>
                                    </form>
                                  </div>
                                </div>
                              </div>
                            <?php $modals .= ob_get_clean(); ?>
                            <tr class="<?= $row_class ?>">
                              <td><?= esc((string)$l['number']) ?></td>
                              <td><?= esc($l['company_name']) ?></td>
                              <td><?= esc(mb_strimwidth($l['description'],0,50,'...')) ?></td>
                              <td>
                                <form method="post">
                                  <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                                  <input type="hidden" name="lead_id" value="<?= esc((string)$l['id']) ?>">
                                  <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <?php foreach (['Good','Bad','N/A'] as $s): ?>
                                      <option value="<?= $s ?>" <?= $l['status']===$s?'selected':'' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                  </select>
                                  <input type="hidden" name="update_lead" value="1">
                                </form>
                              </td>
                              <td>
                                <form method="post">
                                  <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                                  <input type="hidden" name="lead_id" value="<?= esc((string)$l['id']) ?>">
                                  <input type="text" name="notes" class="form-control form-control-sm" value="<?= esc($l['notes']) ?>" onchange="this.form.submit()">
                                  <input type="hidden" name="update_lead" value="1">
                                </form>
                              </td>
                              <td><?= esc(date('H:i', strtotime($l['updated_at']))) ?></td>
                              <td><button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#<?= esc($modal_id) ?>">Edit</button></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                    <?= $modals ?>

                    <!-- Pagination -->
                    <?php if ($mode === 'paged' && $total_rows > $per_page): ?>
                      <?php $total_pages = ceil($total_rows / $per_page); ?>
                      <nav><ul class="pagination justify-content-center">
                        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                          <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?date=<?= esc($selected_date) ?>&mode=paged&page=<?= $p ?>&status_filter=<?= esc($status_filter) ?>"><?= $p ?></a>
                          </li>
                        <?php endfor; ?>
                      </ul></nav>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
   </main>

  <script src="assets/js/bootstrap.bundle.min.js"></script>
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
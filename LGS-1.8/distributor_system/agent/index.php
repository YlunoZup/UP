<?php 
require_once __DIR__ . '/../functions.php';
include __DIR__ . '/../includes/agent_header.php';
require_role('agent');

$db = db_connect();
$me = current_user();
$agent_id = (int)$me['id'];

// Fetch current profile pic
$profile_pic = '';
$stmt = $db->prepare("SELECT file_path FROM profile_pics WHERE user_id = ?");
$stmt->bind_param('i', $agent_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $profile_pic = '../' . $row['file_path'];
}
$stmt->close();

// Date filter
$selected_date = $_GET['date'] ?? 'overall';
$date_condition = '';

if ($selected_date !== 'overall') {
    $safe_date = $db->real_escape_string($selected_date);
    $date_condition = " AND DATE(created_at) = '$safe_date'";
}

$status_options = [
    'N/A',
    'Reviewed',
    'Reviewed - Redesign',
    'Contacted - In Progress',
    'Pending - In Progress',
    'Completed - Paid',
    'Bad'
];

$status_counts = [];
foreach ($status_options as $status) {
    $safe_status = $db->real_escape_string($status);
    $status_counts[$status] = $db->query("SELECT COUNT(*) AS cnt FROM leads WHERE agent_id = $agent_id AND status='$safe_status' $date_condition")->fetch_assoc()['cnt'] ?? 0;
}
$total_leads = array_sum($status_counts);

// --- Additional Stats ---
$days_active = $db->query("SELECT COUNT(DISTINCT DATE(created_at)) AS days FROM leads WHERE agent_id = $agent_id")->fetch_assoc()['days'] ?? 1;
$average_per_day = $days_active > 0 ? round($total_leads / $days_active, 2) : 0;
$completed_leads = $status_counts['Completed - Paid'];
$conversion_rate = $total_leads > 0 ? round(($completed_leads / $total_leads) * 100, 2) : 0;
$recent_leads = $db->query("SELECT COUNT(*) AS cnt FROM leads WHERE agent_id = $agent_id AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['cnt'] ?? 0;

// Peak lead day
$peak_result = $db->query("
    SELECT DATE(created_at) AS peak_date, COUNT(*) AS cnt 
    FROM leads 
    WHERE agent_id = $agent_id 
    GROUP BY DATE(created_at) 
    ORDER BY cnt DESC LIMIT 1
")->fetch_assoc();
$peak_day = $peak_result['peak_date'] ?? 'N/A';
$peak_count = $peak_result['cnt'] ?? 0;

/* Performance Chart Data Fix */
$performance_labels = [];
$performance_data = [];

$safe_completed = $db->real_escape_string('Completed - Paid');

if ($selected_date !== 'overall') {
    for ($i = 0; $i < 24; $i++) {
        $performance_labels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
        $performance_data[$i] = 0;
    }

    $hourly_query = $db->query("
        SELECT HOUR(created_at) AS hour, COUNT(*) AS cnt
        FROM leads
        WHERE agent_id = $agent_id AND status = '$safe_completed' AND DATE(created_at) = '$safe_date'
        GROUP BY HOUR(created_at)
        ORDER BY hour ASC
    ");

    while ($row = $hourly_query->fetch_assoc()) {
        $performance_data[(int)$row['hour']] = (int)$row['cnt'];
    }
    $performance_data = array_values($performance_data);
} else {
    $daily_query = $db->query("
        SELECT DATE(created_at) AS day, COUNT(*) AS cnt
        FROM leads
        WHERE agent_id = $agent_id AND status = '$safe_completed' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY day ASC
    ");

    while ($row = $daily_query->fetch_assoc()) {
        $performance_labels[] = $row['day'];
        $performance_data[] = (int)$row['cnt'];
    }
}

$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Main Dashboard | Lead System</title>

  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/lineicons.css" />
  <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <link rel="stylesheet" href="../assets/css/agent_index.css" />
  <script src="../assets/js/Chart.min.js"></script>
</head>
<body>
  <!-- Sidebar -->
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
            <li><a href="index.php" class="active">Main</a></li>
            <li><a href="table.php">Leads Table</a></li>
          </ul>
        </li>
        <span class="divider"><hr /></span>
        <li class="nav-item">
          <a href="notification.php"><span class="text">Notifications</span></a>
        </li>
        <li class="nav-item">
          <a href="profile.php"><span class="text">Profile</span></a>
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
          <span><?= esc($me['username'] ?? 'Agent') ?></span>
        </div>
      </div>
    </header> 

    <section class="py-4">
      <div class="container-fluid">
        <form class="row g-3 align-items-end mb-4" method="get">
          <div class="col-md-6">
            <label class="form-label fw-bold">Date</label>
            <div class="input-group">
              <a class="btn btn-outline-secondary"
                 href="?date=<?= esc(date('Y-m-d', strtotime(($selected_date === 'overall' ? date('Y-m-d') : $selected_date) . ' -1 day'))) ?>">← Prev</a>
              <input type="date" name="date" value="<?= $selected_date !== 'overall' ? esc($selected_date) : '' ?>" class="form-control" onchange="this.form.submit()">
              <a class="btn btn-outline-secondary" href="?date=<?= esc(date('Y-m-d')) ?>">Today</a>
              <a class="btn btn-outline-secondary" href="?date=overall">Overall</a>
              <a class="btn btn-outline-secondary"
                 href="?date=<?= esc(date('Y-m-d', strtotime(($selected_date === 'overall' ? date('Y-m-d') : $selected_date) . ' +1 day'))) ?>">Next →</a>
            </div>
          </div>
        </form>

        <!-- Two Main Sections -->
        <div class="dashboard-row">
          <div class="card">
            <div class="card-body">
              <h5 class="mb-3">Leads Distribution</h5>
              <div class="chart-container">
                <canvas id="leadsPieChart"></canvas>
              </div>
              <div class="row g-3 mt-3">
                <div class="col-md-3 col-sm-6 col-12"><div class="stat-card"><h3><?= $total_leads ?></h3><p>Total</p></div></div>
                <?php foreach ($status_options as $status): ?>
                <div class="col-md-3 col-sm-6 col-12"><div class="stat-card"><h3><?= $status_counts[$status] ?></h3><p><?= esc($status) ?></p></div></div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-body">
              <h5 class="mb-3">Performance Overview</h5>
              <div class="chart-container">
                <canvas id="performanceChart"></canvas>
              </div>
              <div class="row g-3 mt-3">
                <div class="col-3"><div class="stat-card"><h3><?= $average_per_day ?></h3><p>Avg / Day</p></div></div>
                <div class="col-3"><div class="stat-card"><h3><?= $conversion_rate ?>%</h3><p>Conversion</p></div></div>
                <div class="col-3"><div class="stat-card"><h3><?= $recent_leads ?></h3><p>Last 7 Days</p></div></div>
                <div class="col-3"><div class="stat-card"><h3><?= $peak_count ?></h3><p>Peak Day</p></div></div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <h5 class="mb-3">Quick Overview</h5>
          <div class="card">
            <div class="card-body">
              <p>
                <?= $selected_date === 'overall' 
                  ? 'Showing overall statistics for all time.' 
                  : 'Showing statistics for ' . esc($selected_date) . '.' ?>
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script>
    window.dashboardData = {
      statusCounts: <?= json_encode($status_counts) ?>,
      performanceLabels: <?= json_encode($performance_labels) ?>,
      performanceData: <?= json_encode($performance_data) ?>
    };
  </script>
  <script src="../assets/js/agent_index.js"></script>
</body>
</html>
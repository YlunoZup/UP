<?php 
require_once __DIR__ . '/../functions.php';
require_role('agent');

$db = db_connect();
$me = current_user();
$agent_id = (int)$me['id'];

// Date filter
$selected_date = $_GET['date'] ?? 'overall';
$date_condition = '';

if ($selected_date !== 'overall') {
    $safe_date = $db->real_escape_string($selected_date);
    $date_condition = " AND DATE(created_at) = '$safe_date'";
}

// Fetch stats
$total_leads = $db->query("SELECT COUNT(*) AS cnt FROM leads WHERE agent_id = $agent_id $date_condition")->fetch_assoc()['cnt'] ?? 0;
$good_leads  = $db->query("SELECT COUNT(*) AS cnt FROM leads WHERE agent_id = $agent_id AND status='Good' $date_condition")->fetch_assoc()['cnt'] ?? 0;
$bad_leads   = $db->query("SELECT COUNT(*) AS cnt FROM leads WHERE agent_id = $agent_id AND status='Bad' $date_condition")->fetch_assoc()['cnt'] ?? 0;
$na_leads    = $db->query("SELECT COUNT(*) AS cnt FROM leads WHERE agent_id = $agent_id AND status='N/A' $date_condition")->fetch_assoc()['cnt'] ?? 0;

// --- Additional Stats ---
$days_active = $db->query("SELECT COUNT(DISTINCT DATE(created_at)) AS days FROM leads WHERE agent_id = $agent_id")->fetch_assoc()['days'] ?? 1;
$average_per_day = $days_active > 0 ? round($total_leads / $days_active, 2) : 0;
$conversion_rate = $total_leads > 0 ? round(($good_leads / $total_leads) * 100, 2) : 0;
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

if ($selected_date !== 'overall') {
    for ($i = 0; $i < 24; $i++) {
        $performance_labels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
        $performance_data[$i] = 0;
    }

    $hourly_query = $db->query("
        SELECT HOUR(created_at) AS hour, COUNT(*) AS cnt
        FROM leads
        WHERE agent_id = $agent_id AND status = 'Good' AND DATE(created_at) = '$safe_date'
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
        WHERE agent_id = $agent_id AND status = 'Good' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
      margin-right: 50px; /* consistent with leads.php */
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

    /* Dashboard cards */
    .stat-card {
      background: #fff;
      border-radius: 8px;
      padding: 15px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      text-align: center;
      transition: 0.2s ease-in-out;
    }
    .stat-card:hover { transform: translateY(-2px); }
    .stat-card h3 { margin-bottom: 5px; font-size: 1.2rem; }
    .stat-card p { font-size: 0.85rem; color: #666; }

    .chart-container {
      position: relative;
      min-height: 300px;
      width: 100%;
      max-height: 250px;
    }
    #leadsPieChart {
      max-width: 250px;
      margin: 0 auto;
      display: block;
    }
    .dashboard-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      margin-top: 20px;
    }
    .dashboard-row .card {
      flex: 1 1 48%;
      min-width: 300px;
    }
    @media (max-width: 768px) {
      main.main-wrapper { margin-left: 0; width: 100%; }
      .dashboard-row .card { flex: 1 1 100%; }
    }
  </style>
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
          <div class="profile-circle"><?= esc(strtoupper(substr($_SESSION['user']['username'] ?? 'A', 0, 1))) ?></div>
          <span><?= esc($_SESSION['user']['username'] ?? 'Admin') ?></span>
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
                <div class="col-3"><div class="stat-card"><h3><?= $total_leads ?></h3><p>Total</p></div></div>
                <div class="col-3"><div class="stat-card"><h3><?= $good_leads ?></h3><p>Good</p></div></div>
                <div class="col-3"><div class="stat-card"><h3><?= $bad_leads ?></h3><p>Bad</p></div></div>
                <div class="col-3"><div class="stat-card"><h3><?= $na_leads ?></h3><p>N/A</p></div></div>
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
                <div class="col-4"><div class="stat-card"><h3><?= $average_per_day ?></h3><p>Avg / Day</p></div></div>
                <div class="col-4"><div class="stat-card"><h3><?= $conversion_rate ?>%</h3><p>Conversion</p></div></div>
                <div class="col-4"><div class="stat-card"><h3><?= $recent_leads ?></h3><p>Last 7 Days</p></div></div>
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
    const toggleBtn = document.getElementById('menu-toggle');
    const sidebar = document.querySelector('.sidebar-nav-wrapper');
    const overlay = document.querySelector('.overlay');
    const main = document.querySelector('.main-wrapper');

    toggleBtn.addEventListener('click', () => {
      sidebar.classList.toggle('active');
      overlay.classList.toggle('active');
      if (sidebar.classList.contains('active')) {
        main.style.marginLeft = '250px';
      } else {
        main.style.marginLeft = '0';
      }
    });

    overlay.addEventListener('click', () => {
      sidebar.classList.remove('active');
      overlay.classList.remove('active');
      main.style.marginLeft = '0';
    });

    // Pie Chart
    const pieCtx = document.getElementById('leadsPieChart').getContext('2d');
    new Chart(pieCtx, {
      type: 'pie',
      data: {
        labels: ['Good', 'Bad', 'N/A'],
        datasets: [{
          data: [<?= $good_leads ?>, <?= $bad_leads ?>, <?= $na_leads ?>],
          backgroundColor: ['#28a745', '#dc3545', '#6c757d']
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
      }
    });

    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($performance_labels) ?>,
        datasets: [{
          label: 'Good Leads (Conversions)',
          data: <?= json_encode($performance_data) ?>,
          borderColor: '#007bff',
          backgroundColor: 'rgba(0,123,255,0.1)',
          fill: true,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>
</body>
</html>

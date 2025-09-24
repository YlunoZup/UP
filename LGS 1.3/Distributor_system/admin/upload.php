<?php
require_once __DIR__ . '/../functions.php';
require_role('admin');
$db = db_connect();
$err = $msg = '';

// --- Fetch agents ---
$agents = [];
$res = $db->query("SELECT id, username, full_name FROM users WHERE role='agent' ORDER BY username");
if ($res) $agents = $res->fetch_all(MYSQLI_ASSOC);

/**
 * Validate uploaded CSV file
 */
function validate_csv_upload(array $file): ?string {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) return 'File upload error.';
    if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') return 'Only CSV files allowed.';
    if ($file['size'] > 5 * 1024 * 1024) return 'Max 5MB.';
    return null;
}

/**
 * Save uploaded file to /uploads
 */
function save_uploaded_file(array $file): string|false {
    $uploadDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $dest = $uploadDir . '/upload_' . time() . '_' . bin2hex(random_bytes(6)) . '.csv';
    return move_uploaded_file($file['tmp_name'], $dest) ? $dest : false;
}

/**
 * Parse CSV and insert leads
 */
function import_csv_to_db(mysqli $db, string $filePath, int $agent_id, int $csv_id): int {
    $rowCount = 0;
    $today = date('Y-m-d');
    $stmt = $db->prepare("INSERT INTO leads 
        (number, agent_id, company_name, description, status, notes, lead_date, csv_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    if (($handle = fopen($filePath, 'r')) === false) return 0;
    $row = 0;
    while (($data = fgetcsv($handle, 0, ",")) !== false) {
        $row++;
        if ($row === 1) { // skip header if detected
            $lower = strtolower(implode(',', $data));
            if (str_contains($lower, 'company') || str_contains($lower, 'name')) continue;
        }
        if (!array_filter($data)) continue; // skip empty row

        // Map CSV fields
        $number = trim($data[0] ?? '');
        $company = trim($data[1] ?? '');
        $desc = trim($data[2] ?? '');
        $status = ucfirst(strtolower(trim($data[4] ?? 'N/A')));
        $notes = trim($data[5] ?? '');
        $lead_date = trim($data[6] ?? $today);

        if ($number === '' || $company === '') continue;
        if (!in_array($status, ['Good','Bad','N/A'])) $status = 'N/A';

        $stmt->bind_param('iisssssi', $number, $agent_id, $company, $desc, $status, $notes, $lead_date, $csv_id);
        $stmt->execute();
        $rowCount++;
    }
    fclose($handle);
    $stmt->close();
    return $rowCount;
}

// --- Controller Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $err = 'Invalid CSRF token.';
    } elseif (empty($_POST['agent_id'])) {
        $err = 'Select an agent.';
    } elseif ($error = validate_csv_upload($_FILES['csv'])) {
        $err = $error;
    } elseif (!($dest = save_uploaded_file($_FILES['csv']))) {
        $err = 'Failed to save uploaded file.';
    } else {
        $admin_id = $_SESSION['user_id'];
        $ins_csv = $db->prepare("INSERT INTO uploaded_csvs (agent_id, file_path, uploaded_by) VALUES (?, ?, ?)");
        $ins_csv->bind_param('isi', $_POST['agent_id'], $dest, $admin_id);
        $ins_csv->execute();
        $csv_id = $ins_csv->insert_id;
        $ins_csv->close();

        $count = import_csv_to_db($db, $dest, (int)$_POST['agent_id'], $csv_id);
        $msg = $count > 0 ? "CSV processed: $count leads imported." : "CSV processed but no leads found.";
    }
}
$db->close();

$csrf = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Upload Leads | Lead System</title>
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/lineicons.css" />
  <link rel="stylesheet" href="../assets/css/materialdesignicons.min.css" />
  <link rel="stylesheet" href="../assets/css/fullcalendar.css" />
  <link rel="stylesheet" href="../assets/css/main.css" />
  <style>
    aside.sidebar-nav-wrapper {
      width: 250px; height: 100vh;
      position: fixed; top: 0; left: 0;
      background: #fff; z-index: 1000;
      transform: translateX(-250px);
      transition: transform 0.3s ease;
      display: flex; flex-direction: column;
    }
    aside.sidebar-nav-wrapper.active { transform: translateX(0); }
    main.main-wrapper { margin-left: 0; transition: margin-left 0.3s ease; }
    aside.sidebar-nav-wrapper.active ~ main.main-wrapper { margin-left: 250px; }
    .overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.3); z-index: 999;
      opacity: 0; pointer-events: none; transition: opacity 0.3s ease;
    }
    .overlay.active { opacity: 1; pointer-events: auto; }
    .logout-btn {
      display: block; width: 100%; padding: 10px 15px;
      text-align: center; border: none; color: #dc3545;
      font-size: 14px; font-weight: bold; text-decoration: none; margin-top: auto;
    }
    .logout-btn:hover { color: #a71d2a; }
    .user-profile {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-right: 50px; /* 50px spacing from right edge */
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
            <li><a href="index.php">Agents</a></li>
            <li><a href="leads.php">Leads</a></li>
            <li><a href="upload.php" class="active">Upload</a></li>
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

  <!-- overlay should also start active -->
  <div class="overlay active"></div>


  <!-- Main -->
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
                  <h4 class="mb-3">Upload Leads (CSV)</h4>

                  <?php if ($err): ?><div class="alert alert-danger"><?= esc($err) ?></div><?php endif; ?>
                  <?php if ($msg): ?><div class="alert alert-success"><?= esc($msg) ?></div><?php endif; ?>

                  <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                    <div class="mb-3">
                      <label class="form-label">Agent</label>
                      <select name="agent_id" class="form-select" required>
                        <option value="">-- select agent --</option>
                        <?php if ($agents): ?>
                          <?php foreach ($agents as $a): ?>
                            <option value="<?= esc((string)$a['id']) ?>">
                              <?= esc($a['username'] . ' — ' . $a['full_name']) ?>
                            </option>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <option value="">⚠ No agents found. Please add an agent first.</option>
                        <?php endif; ?>
                      </select>
                    </div>
                    <div class="mb-3">
                      <label class="form-label">CSV file</label>
                      <input type="file" name="csv" accept=".csv" class="form-control" required>
                      <div class="form-text">
                        CSV columns (header optional): id, company_name, description, agent_id, status, notes, lead_date. Max 5MB.
                      </div>
                    </div>
                    <button class="btn btn-primary">Upload and Assign</button>
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

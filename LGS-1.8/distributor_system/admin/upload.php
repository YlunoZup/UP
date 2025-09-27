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
require_once __DIR__ . '/../includes/admin_header.php';
?>
<body>
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="overlay active"></div>

<main class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_navbar.php'; ?>

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

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>
</body>
</html>
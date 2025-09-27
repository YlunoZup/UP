<?php
require_once __DIR__ . '/../functions.php';
require_role('admin');

@session_start();

$db = db_connect();
$err = $msg = '';

// Flash messages
if (!empty($_SESSION['flash_err'])) { $err = $_SESSION['flash_err']; unset($_SESSION['flash_err']); }
if (!empty($_SESSION['flash_msg'])) { $msg = $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); }

// Handle form submissions
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

// Fetch agents
$res = $db->query("SELECT id, username, full_name, role, created_at FROM users WHERE role='agent' ORDER BY id DESC");
$users = [];
while ($row = $res->fetch_assoc()) $users[] = $row;
$db->close();

$csrf = generate_csrf_token();
$username = $_SESSION['user']['username'] ?? 'Admin';
$initial = strtoupper(substr($username, 0, 1));

require_once __DIR__ . '/../includes/admin_header.php';
?>

<body>
<?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>
<div class="overlay active"></div>

<main class="main-wrapper">
    <?php require_once __DIR__ . '/../includes/admin_navbar.php'; ?>

    <section class="container-fluid py-4">
        <div class="row g-4">

            <!-- Agents Table -->
            <div class="col-12">
                <div class="card card-style p-3">
                    <h4 class="mb-3">Agents Management</h4>

                    <?php if ($err): ?>
                        <div class="alert alert-danger"><?= esc($err) ?></div>
                    <?php endif; ?>
                    <?php if ($msg): ?>
                        <div class="alert alert-success"><?= esc($msg) ?></div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full name</th>
                                    <th>Role</th>
                                    <th>Created</th>
                                    <th style="width:160px">Actions</th>
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
                                      <div class="d-flex gap-2">
                                        <!-- Edit button opens modal and loads edit_user.php?id=... in iframe -->
                                        <button type="button" class="btn btn-sm btn-primary edit-user-btn" data-id="<?= esc((string)$u['id']) ?>">
                                          Edit
                                        </button>

                                        <!-- Delete form -->
                                        <form method="post" onsubmit="return confirm('Delete this agent?');" style="margin:0;">
                                            <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                                            <input type="hidden" name="delete_id" value="<?= esc((string)$u['id']) ?>">
                                            <button class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                      </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Create Agent Form -->
            <div class="col-12">
                <div class="card card-style p-3">
                    <h5>Create Agent</h5>
                    <form method="post" class="row g-3">
                        <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Username</label>
                            <input name="username" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Full name</label>
                            <input name="full_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-success w-100">Create Agent</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>
</main>

<!-- Edit user modal (single modal + iframe) -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" style="max-width:820px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Agent</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-0" style="min-height:420px;">
        <iframe id="editUserFrame" src="" frameborder="0" style="width:100%; height:420px;"></iframe>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
  (function () {
    const editButtons = document.querySelectorAll('.edit-user-btn');
    const modalEl = document.getElementById('editUserModal');
    const iframe = document.getElementById('editUserFrame');
    let bsModal = new bootstrap.Modal(modalEl);

    function openEditModal(id) {
      iframe.src = 'edit_user.php?id=' + encodeURIComponent(id);
      bsModal.show();
    }

    editButtons.forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        openEditModal(id);
      });
    });

    // Clear iframe src on hide to stop background activity
    modalEl.addEventListener('hidden.bs.modal', function () {
      iframe.src = '';
    });

    // Listen for postMessage from iframe: when edit succeeds send {type: 'user-updated', id: <id>}
    window.addEventListener('message', function (ev) {
      if (!ev.data || !ev.data.type) return;
      if (ev.data.type === 'user-updated') {
        // close modal then reload page to refresh table
        bsModal.hide();
        // small delay to let modal close animation run
        setTimeout(() => {
          location.reload();
        }, 250);
      }
    });
  })();
</script>

</body>
</html>

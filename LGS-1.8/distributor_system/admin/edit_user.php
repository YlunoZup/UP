<?php
require_once __DIR__ . '/../functions.php';
require_role('admin');

@session_start();

$db = db_connect();
$err = $msg = '';

// Flash messages
if (!empty($_SESSION['flash_err'])) { $err = $_SESSION['flash_err']; unset($_SESSION['flash_err']); }
if (!empty($_SESSION['flash_msg'])) { $msg = $_SESSION['flash_msg']; unset($_SESSION['flash_msg']); }

// Get target user id from GET or POST
$target_id = null;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $target_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
} else {
    // allow POST to include id as hidden field
    $target_id = isset($_POST['id']) ? (int)$_POST['id'] : null;
}

$user = null;
$profile_pic = '';
$username_display = '';
$full_name_display = '';
$initial = 'A';

if (!$target_id || $target_id <= 0) {
    $err = 'Invalid user ID.';
} else {
    // Fetch target user and ensure role = agent
    $stmt = $db->prepare("SELECT id, username, full_name, role FROM users WHERE id = ?");
    $stmt->bind_param('i', $target_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if ($row['role'] !== 'agent') {
            $err = 'You can only edit agent accounts.';
            $stmt->close();
        } else {
            $user = $row;
            $username_display = $user['username'];
            $full_name_display = $user['full_name'];
            $initial = strtoupper(substr($username_display ?: 'A', 0, 1));
            $stmt->close();

            // Fetch current profile pic OR show default initial
            $pp_stmt = $db->prepare("SELECT file_path FROM profile_pics WHERE user_id = ?");
            $pp_stmt->bind_param('i', $target_id);
            $pp_stmt->execute();
            $pp_res = $pp_stmt->get_result();
            if ($pp_row = $pp_res->fetch_assoc()) {
                $profile_pic = '../' . $pp_row['file_path'];
            }
            $pp_stmt->close();
        }
    } else {
        $err = 'User not found.';
        $stmt->close();
    }
}

// Override display values with pending inputs if any (for errors)
if (isset($user)) {
    if (!empty($_SESSION['pending_username'])) {
        $username_display = $_SESSION['pending_username'];
        unset($_SESSION['pending_username']);
    }
    if (!empty($_SESSION['pending_full_name'])) {
        $full_name_display = $_SESSION['pending_full_name'];
        unset($_SESSION['pending_full_name']);
    }
}

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($err) && isset($user)) {
    if (!verify_csrf_token($_POST['csrf'] ?? null)) {
        $err = 'Invalid CSRF token.';
    } else {
        $pending_username = trim($_POST['username'] ?? '');
        $pending_full_name = trim($_POST['full_name'] ?? '');
        $password = $_POST['password'] ?? '';
        $updates = [];
        $params = [];
        $types = '';

        if ($pending_username === '' || $pending_full_name === '') {
            $err = 'Username and full name are required.';
        } else {
            // Check username uniqueness (exclude current target)
            if ($pending_username !== $user['username']) {
                $check_stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                $check_stmt->bind_param('si', $pending_username, $target_id);
                $check_stmt->execute();
                $check_res = $check_stmt->get_result();
                if ($check_res->num_rows > 0) {
                    $err = 'Username already taken.';
                    $check_stmt->close();
                } else {
                    $check_stmt->close();
                    $updates[] = 'username = ?';
                    $params[] = $pending_username;
                    $types .= 's';
                }
            }

            // Always update full_name
            $updates[] = 'full_name = ?';
            $params[] = $pending_full_name;
            $types .= 's';

            // Update password if provided
            if ($password !== '') {
                // server-side minimum check too
                if (mb_strlen($password) < 3) {
                    $err = 'Password must be at least 3 characters.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $updates[] = 'password = ?';
                    $params[] = $hash;
                    $types .= 's';
                }
            }

            if (empty($err) && !empty($updates)) {
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $types .= 'i';
                $params[] = $target_id;
                $stmt = $db->prepare($sql);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $msg = 'User updated successfully.';
                    } else {
                        $msg = 'No changes made.';
                    }
                } else {
                    $err = 'Update failed: ' . $db->error;
                }
                $stmt->close();
            }
        }

        // Handle profile pic upload (separate from username/name updates)
        if (empty($err) && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_pic'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file['type'], $allowed_types)) {
                $err = 'Invalid file type. Allowed: JPG, PNG, GIF, WebP.';
            } elseif ($file['size'] > $max_size) {
                $err = 'File too large. Max 2MB.';
            } else {
                $upload_dir = __DIR__ . '/../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = 'profile_' . $target_id . '_' . time() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;

                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // Delete old pic if exists
                    $old_stmt = $db->prepare("SELECT file_path FROM profile_pics WHERE user_id = ?");
                    $old_stmt->bind_param('i', $target_id);
                    $old_stmt->execute();
                    $old_res = $old_stmt->get_result();
                    if ($old_row = $old_res->fetch_assoc()) {
                        $old_full_path = __DIR__ . '/../' . $old_row['file_path'];
                        if (file_exists($old_full_path)) {
                            @unlink($old_full_path);
                        }
                    }
                    $old_stmt->close();

                    // Insert/replace new
                    $db_path = 'uploads/profiles/' . $file_name;
                    $ins_stmt = $db->prepare("REPLACE INTO profile_pics (user_id, file_path) VALUES (?, ?)");
                    $ins_stmt->bind_param('is', $target_id, $db_path);
                    if ($ins_stmt->execute()) {
                        $msg = $msg ? $msg . ' Profile picture updated.' : 'Profile picture updated.';
                    } else {
                        $err = 'Pic upload failed: ' . $db->error;
                        // cleanup file
                        @unlink($file_path);
                    }
                    $ins_stmt->close();
                } else {
                    $err = 'Failed to upload file.';
                }
            }
        }

        // Always redirect after POST
        if (empty($err)) {
            $_SESSION['flash_msg'] = $msg ?: 'Changes saved.';
            header("Location: edit_user.php?id=" . $target_id);
            exit;
        } else {
            $_SESSION['flash_err'] = $err;
            $_SESSION['pending_username'] = $pending_username;
            $_SESSION['pending_full_name'] = $pending_full_name;
            header("Location: edit_user.php?id=" . $target_id);
            exit;
        }
    }
}

$db->close();
$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Edit Agent | Lead System</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="../assets/css/agent_profile.css" />
  <style>
    body { padding: 12px; background: transparent; }
    .card-style { box-shadow: none; border: none; padding: 0; }
  </style>
</head>
<body>
  <div class="card card-style p-3">
    <?php if (isset($user)): ?>
      <h5 class="mb-3 text-center">Edit Agent: <?= esc($username_display) ?></h5>

      <?php if ($err): ?>
        <div class="alert alert-danger"><?= esc($err) ?></div>
      <?php endif; ?>
      <?php if ($msg): ?>
        <div class="alert alert-success"><?= esc($msg) ?></div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data" id="edit-user-form">
        <input type="hidden" name="csrf" value="<?= esc($csrf) ?>">
        <input type="hidden" name="id" value="<?= esc((string)$target_id) ?>">

        <div class="mb-3 text-center">
          <?php if ($profile_pic && file_exists($profile_pic)): ?>
            <img src="<?= esc($profile_pic) ?>" alt="Profile Pic" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
          <?php else: ?>
            <div class="profile-circle d-inline-block"><?= esc($initial) ?></div>
          <?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label">Profile Picture</label>
          <input type="file" name="profile_pic" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
          <div class="form-text">Max 2MB. JPG, PNG, GIF, WebP only.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" value="<?= esc($username_display) ?>" required>
          <div class="form-text">Must be unique.</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="full_name" class="form-control" value="<?= esc($full_name_display) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" name="password" class="form-control" id="password-input">
          <div class="form-text">Minimum 3 characters.</div>
        </div>

        <div class="d-grid">
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </form>
    <?php else: ?>
      <?php if ($err): ?>
        <div class="alert alert-danger"><?= esc($err) ?></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <script src="../assets/js/bootstrap.bundle.min.js"></script>
  <script>
    // Client-side password checks and confirmation (same UX as agent profile)
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('edit-user-form');
      const passwordInput = document.getElementById('password-input');

      if (!form) return;

      form.addEventListener('submit', function (e) {
        const pwd = passwordInput.value.trim();

        if (pwd === '') return; // no password change

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

      // If success message present, notify parent after brief delay so modal can close & reload table
      <?php if (!empty($msg)): ?>
        setTimeout(function() {
          try {
            if (window.parent) {
              window.parent.postMessage({ type: 'user-updated', id: <?= json_encode($target_id) ?> }, '*');
            }
          } catch (e) {
            // ignore
          }
        }, 1500);
      <?php endif; ?>
    });
  </script>
</body>
</html>
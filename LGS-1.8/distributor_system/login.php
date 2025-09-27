<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = $_SESSION['role'];
    header('Location: ' . ($role === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/agent/index.php'));
    exit;
}

// Handle login attempt
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $err = handle_login($_POST['username'] ?? '', $_POST['password'] ?? '');
}

include __DIR__ . '/includes/auth_header.php';
?>

<div class="auth-wrapper">
  <div class="overlay-content">
    <!-- Welcome Text -->
    <div class="welcome-text mb-4">
      <h1 class="text-primary mb-10">Welcome Back</h1>
    </div>

    <!-- Sign In Form -->
    <div class="form-wrapper">
      <h6 class="mb-15">Sign in to your existing account to continue</h6>

      <?php if ($err): ?>
        <div class="alert alert-danger"><?= esc($err) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="row">
          <div class="col-12">
            <div class="input-style-1">
              <label>Email / Username</label>
              <input type="text" name="username" placeholder="Username" required />
            </div>
          </div>
          <div class="col-12">
            <div class="input-style-1">
              <label>Password</label>
              <input type="password" name="password" placeholder="Password" required />
            </div>
          </div>
          <div class="col-xxl-6 col-lg-12 col-md-6">
            <div class="form-check checkbox-style mb-30">
              <input class="form-check-input" type="checkbox" id="checkbox-remember" />
              <label class="form-check-label" for="checkbox-remember">Remember me next time</label>
            </div>
          </div>
          <div class="col-xxl-6 col-lg-12 col-md-6">
            <div class="text-start text-md-end text-lg-start text-xxl-end mb-30">
              <a href="help.php" class="hover-underline">Forgot Password?</a>
            </div>
          </div>
          <div class="col-12">
            <button class="main-btn primary-btn btn-hover w-100 text-center">
              Sign In
            </button>
          </div>
        </div>
      </form>

      <!-- Bottom Social Buttons -->
      <div class="signin-bottom">
        <p class="text-sm text-medium text-gray mb-3">Easy Sign In With</p>
        <div class="button-group-social">
          <button class="main-btn primary-btn-outline">
            <i class="lni lni-facebook-fill mr-10"></i> Facebook
          </button>
          <button class="main-btn danger-btn-outline">
            <i class="lni lni-google mr-10"></i> Google
          </button>
        </div>
        <p class="text-sm text-medium text-dark text-center mt-4">
          Don’t have an account yet?
          <a href="help.php">Create an account</a>
        </p>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/includes/auth_footer.php'; ?>

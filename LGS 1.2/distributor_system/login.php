<?php
require_once __DIR__ . '/functions.php';

if (is_logged_in()) {
    $role = $_SESSION['role'];
    header('Location: ' . ($role === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/agent/index.php'));
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $err = 'Username and password required.';
    } else {
        $db = db_connect();
        $stmt = $db->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $uname, $hash, $full, $role);
            $stmt->fetch();
            if (password_verify($password, $hash)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $id;
                $_SESSION['username'] = $uname;
                $_SESSION['full_name'] = $full;
                $_SESSION['role'] = $role;
                generate_csrf_token();
                header('Location: ' . ($role === 'admin' ? BASE_URL . '/admin/index.php' : BASE_URL . '/agent/index.php'));
                exit;
            } else {
                $err = 'Invalid credentials.';
            }
        } else {
            $err = 'Invalid credentials.';
        }
        $stmt->close();
        $db->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="shortcut icon" href="assets/images/favicon.svg" type="image/x-icon" />
    <title>Sign In | Lead System</title>

    <!-- ========== All CSS files linkup ========= -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/css/lineicons.css" />
    <link rel="stylesheet" href="assets/css/materialdesignicons.min.css" />
    <link rel="stylesheet" href="assets/css/fullcalendar.css" />
    <link rel="stylesheet" href="assets/css/main.css" />

    <style>
      /* Full screen base */
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden; /* No scroll bars */
      }

      /* Parent container full screen */
      .auth-row {
        height: 100vh;
        width: 100%;
        display: flex;
        flex-wrap: wrap;
        margin: 0;
      }

      /* Left side styling */
      .col-left {
        background: #f0f4ff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 30px;
      }

      .col-left img {
        max-width: 100%;
        height: auto;
      }

      /* Right side styling */
      .col-right {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 40px;
      }

      .form-wrapper {
        width: 100%;
        max-width: 420px;
        margin: 0 auto;
      }

      /* Make the form start at the top */
      .form-top {
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
      }

      /* Sign in options at the bottom */
      .signin-bottom {
        text-align: center;
        padding-top: 20px;
      }

      /* Facebook and Google side by side */
      .button-group-social {
        display: flex;
        justify-content: center;
        gap: 15px;
      }

      .button-group-social button {
        flex: 1;
      }

      /* Responsive */
      @media (max-width: 991.98px) {
        .auth-row {
          flex-direction: column;
          overflow-y: auto;
        }
        body {
          overflow-y: auto;
        }
        .col-left, .col-right {
          height: auto;
        }
        .col-right {
          padding: 20px;
        }
      }
    </style>
  </head>
  <body>
    <div class="container-fluid g-0">
      <div class="row g-0 auth-row">
        <!-- Left Section -->
        <div class="col-lg-6 col-left">
          <div class="title mb-4">
            <h1 class="text-primary mb-10">Welcome Back</h1>
            <p class="text-medium">Sign in to your existing account to continue</p>
          </div>
          <div class="cover-image mb-3">
            <img src="assets/images/auth/signin-image.svg" alt="Sign in illustration" />
          </div>
          <div class="shape-image">
            <img src="assets/images/auth/shape.svg" alt="Decorative shape" />
          </div>
        </div>

        <!-- Right Section -->
        <div class="col-lg-6 col-right">
          <div class="form-top">
            <div class="form-wrapper">
              <h6 class="mb-15">Sign In Form</h6>
              <p class="text-sm mb-25">
                Start creating the best possible user experience for your customers.
              </p>

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
                      <a href="reset-password.html" class="hover-underline">Forgot Password?</a>
                    </div>
                  </div>
                  <div class="col-12">
                    <button class="main-btn primary-btn btn-hover w-100 text-center">
                      Sign In
                    </button>
                  </div>
                </div>
              </form>
            </div>
          </div>

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
              <a href="signup.php">Create an account</a>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- JS Files -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/Chart.min.js"></script>
    <script src="assets/js/dynamic-pie-chart.js"></script>
    <script src="assets/js/moment.min.js"></script>
    <script src="assets/js/fullcalendar.js"></script>
    <script src="assets/js/jvectormap.min.js"></script>
    <script src="assets/js/world-merc.js"></script>
    <script src="assets/js/polyfill.js"></script>
    <script src="assets/js/main.js"></script>
  </body>
</html>

<?php
function handle_login(string $username, string $password): string {
    $username = trim($username);
    if ($username === '' || $password === '') {
        return 'Username and password required.';
    }

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
        }
    }

    $stmt->close();
    $db->close();
    return 'Invalid credentials.';
}

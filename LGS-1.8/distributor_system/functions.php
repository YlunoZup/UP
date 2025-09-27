<?php
require_once __DIR__ . '/config.php';

function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || !$token) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']) || !empty($_SESSION['user_id']);
}

function current_user(): array {
    // Prefer full user record if available in session
    if (!empty($_SESSION['user']) && is_array($_SESSION['user'])) {
        return $_SESSION['user'];
    }

    // Fallback to legacy session keys
    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'username'  => $_SESSION['username'] ?? null,
        'role'      => $_SESSION['role'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
    ];
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    $me = current_user();
    if (($me['role'] ?? '') !== $role) {
        http_response_code(403);
        echo "Forbidden — insufficient permissions.";
        exit;
    }
}

// helper: bind params
if (!function_exists('stmt_bind_params')) {
    /**
     * Bind params to a mysqli_stmt with a types string and array of values.
     * Returns true on success, false on failure.
     */
    function stmt_bind_params(mysqli_stmt $stmt, string $types, array $params): bool {
        // mysqli::bind_param requires references
        $refs = [];
        foreach ($params as $i => $v) {
            $refs[$i] = &$params[$i];
        }
        array_unshift($refs, $types);
        return (bool) call_user_func_array([$stmt, 'bind_param'], $refs);
    }
}

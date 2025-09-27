<?php
require_once __DIR__ . '/../functions.php';
require_role('agent');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}
if (!verify_csrf_token($_POST['csrf'] ?? null)) {
    die('Invalid CSRF token.');
}

$lead_id = (int)($_POST['lead_id'] ?? 0);
$status = $_POST['status'] ?? 'Other';
$notes = trim($_POST['notes'] ?? '');

$me = current_user();
$agent_id = (int)$me['id'];
$db = db_connect();

// verify lead belongs to this agent
$stmt = $db->prepare("SELECT id FROM leads WHERE id = ? AND agent_id = ?");
$stmt->bind_param('ii', $lead_id, $agent_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows !== 1) {
    $stmt->close();
    $db->close();
    die('Lead not found or not assigned to you.');
}
$stmt->close();

$upd = $db->prepare("UPDATE leads SET status = ?, notes = ? WHERE id = ?");
$upd->bind_param('ssi', $status, $notes, $lead_id);
if ($upd->execute()) {
    $upd->close();
    $db->close();
    header('Location: ' . BASE_URL . '/agent/index.php'); exit;
} else {
    $upd->close();
    $db->close();
    die('Failed to update lead.');
}

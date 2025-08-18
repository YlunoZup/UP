<?php
session_start();
require_once "config.php";

// Handle Login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM agents WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $agent = $result->fetch_assoc();
        if (hash('sha256', $password) === $agent['password']) {
            $_SESSION['agent_id'] = $agent['id'];
            $_SESSION['agent_name'] = $agent['full_name'];
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Agent not found.";
    }
}

// Handle Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: agent_panel.php");
    exit();
}

// If logged in, fetch leads
$leads = [];
if (isset($_SESSION['agent_id'])) {
    $stmt = $conn->prepare("SELECT * FROM leads WHERE agent_id = ?");
    $stmt->bind_param("i", $_SESSION['agent_id']);
    $stmt->execute();
    $leads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Agent Panel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background: #f4f4f4; }
        .error { color: red; }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['agent_id'])): ?>
    <h2>Agent Login</h2>
    <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button type="submit" name="login">Login</button>
    </form>
<?php else: ?>
    <h2>Welcome, <?= htmlspecialchars($_SESSION['agent_name']) ?>!</h2>
    <a href="?logout=1">Logout</a>

    <h3>Your Leads</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Customer Name</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Status</th>
            <th>Created</th>
        </tr>
        <?php foreach ($leads as $lead): ?>
        <tr>
            <td><?= $lead['id'] ?></td>
            <td><?= htmlspecialchars($lead['customer_name']) ?></td>
            <td><?= htmlspecialchars($lead['contact_number']) ?></td>
            <td><?= htmlspecialchars($lead['email']) ?></td>
            <td><?= htmlspecialchars($lead['status']) ?></td>
            <td><?= $lead['created_at'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
<?php endif; ?>

</body>
</html>

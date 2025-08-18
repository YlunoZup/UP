<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Handle New Agent Creation
if (isset($_POST['create_agent'])) {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO agents (username, password, full_name, role) VALUES (?, ?, ?, 'agent')");
    $stmt->bind_param("sss", $username, $password, $full_name);
    $stmt->execute();
}

// Handle CSV Upload
if (isset($_POST['upload_csv']) && isset($_FILES['csv_file'])) {
    $agent_id = $_POST['agent_id'];
    $today = date('Y-m-d');

    // Remove existing leads for today
    $conn->query("DELETE FROM leads WHERE agent_id = $agent_id AND date_assigned = '$today'");

    $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
    while (($row = fgetcsv($file)) !== false) {
        $number = $row[0];
        $company = $row[1] ?? '';
        $desc = $row[2] ?? '';

        $stmt = $conn->prepare("INSERT INTO leads (agent_id, number, company_name, description, date_assigned) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $agent_id, $number, $company, $desc, $today);
        $stmt->execute();
    }
    fclose($file);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding-top: 56px; }
        .offcanvas-body a { display: block; padding: 8px 0; text-decoration: none; color: black; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <span class="navbar-brand">Admin Panel</span>
    </div>
</nav>

<div class="offcanvas offcanvas-start" id="menu">
    <div class="offcanvas-header">
        <h5>Menu</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <a href="?view=agents">Agents</a>
        <a href="?view=add_agent">Add Agent</a>
    </div>
</div>

<div class="container mt-4">
<?php
$view = $_GET['view'] ?? 'agents';

if ($view == 'agents') {
    echo "<h3>Agents List</h3>";
    $result = $conn->query("SELECT * FROM agents WHERE role = 'agent'");
    echo "<table class='table table-bordered'><tr><th>Name</th><th>Username</th><th>Actions</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>{$row['full_name']}</td>
                <td>{$row['username']}</td>
                <td>
                    <form method='POST' enctype='multipart/form-data' class='d-inline'>
                        <input type='hidden' name='agent_id' value='{$row['id']}'>
                        <input type='file' name='csv_file' required>
                        <button class='btn btn-sm btn-primary' name='upload_csv'>Upload Today's Leads</button>
                    </form>
                    <a class='btn btn-sm btn-info' href='view_leads.php?agent_id={$row['id']}'>View Current</a>
                    <a class='btn btn-sm btn-secondary' href='view_history.php?agent_id={$row['id']}'>History</a>
                </td>
              </tr>";
    }
    echo "</table>";
}
elseif ($view == 'add_agent') {
    ?>
    <h3>Add New Agent</h3>
    <form method="POST">
        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="full_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Username</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-success" name="create_agent">Create Agent</button>
    </form>
    <?php
}
?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

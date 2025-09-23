<?php
// ONLY RUN ONCE
// Might have to make a new phpMyAdmin User and Pass based on "$DB" values
// goto http://localhost/(foldername)
// select setup.php, wait for it to finish running 
// EDIT THESE BEFORE RUNNING:
$DB_HOST = '127.0.0.1';
$DB_USER = 'lead_user';
$DB_PASS = '1122';
$DB_NAME = 'lead_system';

// Users to create
$USERS = [
    ['username' => 'admin', 'full_name' => 'Administrator', 'password' => '123', 'role' => 'admin'],
    ['username' => 'agent', 'full_name' => 'Agent Smith', 'password' => '123', 'role' => 'agent'],
];

// Connect
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS);
if ($mysqli->connect_errno) {
    die("Connect failed: " . $mysqli->connect_error);
}

// Create DB
if (!$mysqli->query("CREATE DATABASE IF NOT EXISTS `{$DB_NAME}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
    die("DB create error: " . $mysqli->error);
}

$mysqli->select_db($DB_NAME);

// Create tables in correct order
$queries = [
    // Users first
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin','agent') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB;",

    // Uploaded CSVs second
    "CREATE TABLE IF NOT EXISTS uploaded_csvs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agent_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        uploaded_by INT NULL,  -- nullable for ON DELETE SET NULL
        FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;",

    // Leads last
    "CREATE TABLE IF NOT EXISTS leads (
        id INT AUTO_INCREMENT PRIMARY KEY,
        number BIGINT NOT NULL, -- stores CSV id/number
        agent_id INT NOT NULL,
        company_name VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('Good','Bad','N/A') DEFAULT 'N/A',
        notes TEXT,
        lead_date DATE NOT NULL,
        csv_id INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (csv_id) REFERENCES uploaded_csvs(id) ON DELETE SET NULL
    ) ENGINE=InnoDB;"
];

// Execute table creation
foreach ($queries as $q) {
    if (!$mysqli->query($q)) {
        die("Table create error: " . $mysqli->error);
    }
}

// Create users if not exists
foreach ($USERS as $u) {
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param('s', $u['username']);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $ins = $mysqli->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?,?,?,?)");
        $ins->bind_param('ssss', $u['username'], $hash, $u['full_name'], $u['role']);
        if (!$ins->execute()) {
            die("User insert error: " . $mysqli->error);
        }
        echo ucfirst($u['role']) . " user '{$u['username']}' created. Password: '{$u['password']}'.<br>";
        $ins->close();
    } else {
        echo ucfirst($u['role']) . " user '{$u['username']}' already exists.<br>";
    }
    $stmt->close();
}

echo "Setup complete. Delete setup.php now for security.";
$mysqli->close();

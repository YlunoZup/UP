    <?php
// Run once, then delete this file for security
$password = '123'; // <- change this if you want a different default password
echo password_hash($password, PASSWORD_DEFAULT);

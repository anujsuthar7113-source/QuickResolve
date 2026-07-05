<?php
// Run this file ONCE after importing the database to set
// correct bcrypt hashes for the seeded demo accounts.
// Visit: http://localhost/QuickResolve_18/fix_passwords.php
// DELETE THIS FILE AFTER RUNNING IT!

require_once 'config/db_18.php';

// Define correct passwords for demo accounts
$accounts = [
    ['email' => 'admin@quickresolve.com',        'password' => 'Admin@123'],
    ['email' => 'electrical@quickresolve.com',   'password' => 'Dept@123'],
    ['email' => 'plumbing@quickresolve.com',      'password' => 'Dept@123'],
    ['email' => 'housekeeping@quickresolve.com',  'password' => 'Dept@123'],
    ['email' => 'itsupport@quickresolve.com',     'password' => 'Dept@123'],
    ['email' => 'maintenance@quickresolve.com',   'password' => 'Dept@123'],
    ['email' => 'security@quickresolve.com',      'password' => 'Dept@123'],
    ['email' => 'rahul@example.com',              'password' => 'User@123'],
    ['email' => 'priya@example.com',              'password' => 'User@123'],
];

$updated = 0;
foreach ($accounts as $acc) {
    $hash  = password_hash($acc['password'], PASSWORD_DEFAULT);
    $email = $acc['email'];
    $stmt  = $conn->prepare("UPDATE users SET password=? WHERE email=?");
    $stmt->bind_param('ss', $hash, $email);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $updated++;
        echo "<p style='color:green'>✓ Updated: {$acc['email']} → {$acc['password']}</p>";
    } else {
        echo "<p style='color:orange'>⚠ Not found or unchanged: {$acc['email']}</p>";
    }
    $stmt->close();
}

echo "<hr>";
echo "<h3 style='color:green'>Done! $updated account(s) updated.</h3>";
echo "<p><strong style='color:red'>IMPORTANT: Delete this file immediately after running it!</strong></p>";
echo "<p><a href='login.php'>Go to Login →</a></p>";
echo "<br><h4>Login Credentials:</h4>";
echo "<ul>
    <li><strong>Admin:</strong> admin@quickresolve.com / Admin@123</li>
    <li><strong>Departments:</strong> electrical@quickresolve.com / Dept@123 (same for all dept accounts)</li>
    <li><strong>Users:</strong> rahul@example.com / User@123</li>
</ul>";
?>
